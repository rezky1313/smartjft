<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

// Model yang dipakai untuk resolve relasi
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;       // unit kerja (no_rs)
use App\Models\Jenjangjabatan;   // jenjang
// NOTE: Sdmmodels tidak dipakai di command ini (hanya di seeder hasil generate)

class GenerateSdmSeederFromExcel extends Command
{
    protected $signature = 'sdm:make-seeder-from-excel 
        {--file= : Path file Excel/CSV, contoh: storage/app/import/sdm.xlsx}
        {--class= : Nama class seeder (opsional)}
        {--date-format= : Format tanggal di Excel jika string, contoh: d/m/Y (opsional)}
        {--sheet=0 : Index sheet (0 = pertama)}';

    protected $description = 'Baca Excel/CSV SDM dan generate seeder PHP di database/seeders/';

    // Header yang dikenali (boleh subset)
    protected array $supported = [
        'nip','nik','nama_lengkap','jenis_kelamin',
        'pendidikan_terakhir','pangkat_golongan','status_kepegawaian',
        'formasi_jabatan_id','formasi_nama',
        'jenjang_nama','tahun_formasi',
        'unit_kerja_id','unit_kerja_kode','unit_kerja_nama',
        'tmt_pengangkatan','aktif',
    ];

    public function handle(): int
    {
        $path       = $this->option('file') ?: 'storage/app/import/sdm.xlsx';
        $sheetIndex = (int) $this->option('sheet');
        $dateFormat = $this->option('date-format'); // contoh: d/m/Y

        if (!File::exists(base_path($path))) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        // Load file
        try {
            $spreadsheet = IOFactory::load(base_path($path));
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: '.$e->getMessage());
            return self::FAILURE;
        }

        // Ambil sheet
        try {
            $sheet = $spreadsheet->getSheet($sheetIndex);
        } catch (\Throwable $e) {
            $this->error('Sheet index tidak valid: '.$sheetIndex);
            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true); // key kolom: A,B,C,...
        if (count($rows) < 2) {
            $this->warn('Sheet kosong / tidak ada data.');
            return self::SUCCESS;
        }

        // Parse header
        $headerRow = array_shift($rows);
        $headers   = [];
        foreach ($headerRow as $col => $name) {
            $name = trim(Str::snake(Str::lower((string)$name)));
            if ($name !== '' && in_array($name, $this->supported, true)) {
                $headers[$col] = $name;
            }
        }

        if (!count($headers)) {
            $this->error('Header tidak dikenali. Pastikan memakai header seperti: '.implode(', ', $this->supported));
            return self::FAILURE;
        }

        $data      = [];
        $warnings  = [];
        $processed = 0;

        foreach ($rows as $r) {
            // Buat associative row berdasar header
            $row = [];
            foreach ($headers as $col => $name) {
                $row[$name] = isset($r[$col]) ? trim((string)$r[$col]) : null;
            }

            // Skip baris yang benar2 kosong
            if (!($row['nama_lengkap'] ?? null) && !($row['nip'] ?? null)) {
                continue;
            }

            // --- Resolve UNIT KERJA ---
            $unitKerjaId = null; // no_rs
            if (!empty($row['unit_kerja_id'])) {
                $unitKerjaId = (int) $row['unit_kerja_id'];
            } elseif (!empty($row['unit_kerja_kode'])) {
                $unitKerjaId = Rumahsakit::where('no_rs', (int) $row['unit_kerja_kode'])->value('no_rs');
            } elseif (!empty($row['unit_kerja_nama'])) {
                $unitKerjaId = Rumahsakit::where('nama_rumahsakit', $row['unit_kerja_nama'])->value('no_rs');
            }

            // --- Resolve FORMASI ---
            $formasiId = null;

            if (!empty($row['formasi_jabatan_id'])) {
                $formasiId = (int) $row['formasi_jabatan_id'];
            } else {
                // a) pakai jenjang_nama => jenjang_id
                $jenjangId = null;
                if (!empty($row['jenjang_nama'])) {
                    $jenjangId = Jenjangjabatan::where('nama_jenjang', $row['jenjang_nama'])->value('id');
                    if (!$jenjangId) {
                        $warnings[] = "Jenjang tidak ditemukan: '{$row['jenjang_nama']}' (baris: ".($processed+2).")";
                    }
                }

                // b) kalau ada unit + jenjang, cari formasi (opsional filter tahun)
                if ($jenjangId && $unitKerjaId) {
                    $q = Formasijabatan::where('jenjang_id', $jenjangId)
                        ->where('unit_kerja_id', $unitKerjaId);

                    if (!empty($row['tahun_formasi'])) {
                        $q->where('tahun_formasi', (int) $row['tahun_formasi']);
                    }

                    $formasiId = $q->orderByDesc('tahun_formasi')->value('id');
                    if (!$formasiId) {
                        $warnings[] = "Formasi tidak ditemukan untuk unit={$unitKerjaId}, jenjang={$row['jenjang_nama']}, tahun=".($row['tahun_formasi'] ?? 'null')." (baris: ".($processed+2).")";
                    }
                }

                // c) fallback lama: formasi_nama (jika ada)
                if (!$formasiId && !empty($row['formasi_nama'])) {
                    $formasiId = Formasijabatan::where('nama_formasi', $row['formasi_nama'])->value('id');
                    if (!$formasiId) {
                        $warnings[] = "Formasi nama tidak ditemukan: '{$row['formasi_nama']}' (baris: ".($processed+2).")";
                    }
                }
            }

            // Jika formasi ketemu tapi unit kosong, tarik unit dari formasi
            if ($formasiId && !$unitKerjaId) {
                $unitKerjaId = Formasijabatan::whereKey($formasiId)->value('unit_kerja_id');
            }

            // --- Parse TMT ---
            $tmt = null;
            if (!empty($row['tmt_pengangkatan'])) {
                $raw = $row['tmt_pengangkatan'];
                try {
                    if (is_numeric($raw)) {
                        $tmt = Carbon::parse(ExcelDate::excelToDateTimeObject($raw))->format('Y-m-d');
                    } else {
                        $tmt = $dateFormat
                            ? Carbon::createFromFormat($dateFormat, $raw)->format('Y-m-d')
                            : Carbon::parse($raw)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $warnings[] = "Gagal parse tanggal TMT '{$raw}' (baris: ".($processed+2).")";
                    $tmt = null;
                }
            }

            // --- Aktif ---
            $aktif = 1;
            if (isset($row['aktif']) && $row['aktif'] !== '') {
                $aktif = in_array(Str::lower($row['aktif']), ['1','ya','true','aktif','y'], true) ? 1 : 0;
            }

            // Build 1 record (sesuaikan Sdmmodels::$fillable)
            $item = [
                'nip'                 => $row['nip']                 ?? null,
                'nik'                 => $row['nik']                 ?? null,
                'nama_lengkap'        => $row['nama_lengkap']        ?? null,
                'jenis_kelamin'       => $row['jenis_kelamin']       ?? null,
                'pendidikan_terakhir' => $row['pendidikan_terakhir'] ?? null,
                'pangkat_golongan'    => $row['pangkat_golongan']    ?? null,
                'status_kepegawaian'  => $row['status_kepegawaian']  ?? 'PNS',
                'formasi_jabatan_id'  => $formasiId,
                'unit_kerja_id'       => $unitKerjaId,
                'tmt_pengangkatan'    => $tmt,
                'aktif'               => $aktif,
                'created_at'          => now()->toDateTimeString(),
                'updated_at'          => now()->toDateTimeString(),
            ];

            $data[] = $item;
            $processed++;
        }

        if (!count($data)) {
            $this->warn('Tidak ada baris valid untuk dibuatkan seeder.');
            return self::SUCCESS;
        }

        // Tentukan nama class seeder
        $class = $this->option('class') ?: ('SdmFromExcelSeeder'.now()->format('YmdHis'));
        $file  = base_path("database/seeders/{$class}.php");

        // Export array ke PHP literal
        $phpArray = var_export($data, true);

        // Buat isi file seeder
        $stub = <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use App\\Models\\Sdmmodels;

class {$class} extends Seeder
{
    public function run(): void
    {
        \$rows = {$phpArray};

        // Idempotent by NIP (updateOrCreate), agar aman dijalankan berulang
        foreach (array_chunk(\$rows, 500) as \$chunk) {
            foreach (\$chunk as \$row) {
                // Jika NIP kosong, pakai create biasa
                if (!empty(\$row['nip'])) {
                    Sdmmodels::withTrashed()->updateOrCreate(
                        ['nip' => \$row['nip']],
                        \$row
                    );
                } else {
                    Sdmmodels::create(\$row);
                }
            }
        }
    }
}

PHP;

        File::put($file, $stub);
        $this->info("Seeder dibuat: database/seeders/{$class}.php");
        $this->line("Jalankan: php artisan db:seed --class={$class}");

        // Tampilkan warning (jika ada)
        if (count($warnings)) {
            $this->newLine();
            $this->warn('PERINGATAN (silakan tinjau):');
            foreach ($warnings as $w) $this->line(" - ".$w);
        }

        return self::SUCCESS;
    }
}
