<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Model
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;       // unit kerja (no_rs)
use App\Models\Jenjangjabatan;   // jenjang

class GenerateFormasiSeederFromExcel extends Command
{
    protected $signature = 'formasi:make-seeder-from-excel
        {--file= : Path file Excel/CSV, contoh: storage/app/import/formasi.xlsx}
        {--class= : Nama class seeder (opsional)}
        {--sheet=0 : Index sheet (0 = pertama)}';

    protected $description = 'Baca Excel/CSV Formasi Jabatan dan generate seeder PHP di database/seeders/';

    /**
     * Alias header Excel -> key internal
     * Kamu boleh pakai salah satu nama header berikut.
     */
    protected array $headerAlias = [
        // identitas unit kerja
        'unit_kerja_id'      => 'unit_kerja_id',      // langsung no_rs
        'unit_kerja_kode'    => 'unit_kerja_kode',    // alias no_rs
        'no_rs'              => 'unit_kerja_kode',
        'unit_kerja'         => 'unit_kerja_nama',
        'unit_kerja_nama'    => 'unit_kerja_nama',
        'nama_unit_kerja'    => 'unit_kerja_nama',
        'nama_rumahsakit'    => 'unit_kerja_nama',

        // jenjang
        'jenjang_id'         => 'jenjang_id',
        'jenjang'            => 'jenjang_nama',
        'nama_jenjang'       => 'jenjang_nama',
        'jenjang_nama'       => 'jenjang_nama',

        // properti formasi
        'nama_formasi'       => 'nama_formasi',       // opsional; kalau kosong akan diisi nama jenjang
        'kuota'              => 'kuota',
        'tahun'              => 'tahun_formasi',
        'tahun_formasi'      => 'tahun_formasi',

        // opsional lain, diabaikan kalau ada
        'catatan'            => 'catatan',
    ];

    public function handle(): int
    {
        $path       = $this->option('file') ?: 'storage/app/import/formasi.xlsx';
        $sheetIndex = (int) $this->option('sheet');

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

        $rows = $sheet->toArray(null, true, true, true); // A,B,C,...
        if (count($rows) < 2) {
            $this->warn('Sheet kosong / tidak ada data.');
            return self::SUCCESS;
        }

        // --- Baca header & petakan alias ---
        $rawHeader = array_shift($rows);
        $headers   = []; // ex: 'A' => 'unit_kerja_nama'
        foreach ($rawHeader as $col => $name) {
            $norm = Str::snake(Str::lower((string)$name));
            if (isset($this->headerAlias[$norm])) {
                $headers[$col] = $this->headerAlias[$norm];
            }
        }

        // Minimal wajib: unit kerja + jenjang + kuota (tahun disarankan)
        if (!in_array('unit_kerja_id', $headers, true)
            && !in_array('unit_kerja_kode', $headers, true)
            && !in_array('unit_kerja_nama', $headers, true)) {
            $this->error('Header unit kerja tidak ditemukan. Gunakan salah satu: unit_kerja_id / unit_kerja_kode / no_rs / unit_kerja / unit_kerja_nama / nama_unit_kerja / nama_rumahsakit');
            return self::FAILURE;
        }
        if (!in_array('jenjang_id', $headers, true) && !in_array('jenjang_nama', $headers, true)) {
            $this->error('Header jenjang tidak ditemukan. Gunakan salah satu: jenjang_id / jenjang / nama_jenjang / jenjang_nama');
            return self::FAILURE;
        }
        if (!in_array('kuota', $headers, true)) {
            $this->error('Header "kuota" wajib ada.');
            return self::FAILURE;
        }

        $data      = [];
        $warnings  = [];
        $processed = 0;

        foreach ($rows as $idx => $r) {
            // Bangun row asosiatif berdasar headers
            $row = [];
            foreach ($headers as $col => $key) {
                $row[$key] = isset($r[$col]) ? trim((string)$r[$col]) : null;
            }

            // --- Resolve Unit Kerja (no_rs) ---
            $unitKerjaId = null; // no_rs
            if (!empty($row['unit_kerja_id'])) {
                $unitKerjaId = (int) $row['unit_kerja_id'];
                // validasi eksistensi (opsional)
                if (!Rumahsakit::where('no_rs', $unitKerjaId)->exists()) {
                    $warnings[] = "unit_kerja_id {$unitKerjaId} tidak ditemukan (baris ".($idx+2).")";
                    $unitKerjaId = null;
                }
            } elseif (!empty($row['unit_kerja_kode'])) {
                $unitKerjaId = Rumahsakit::where('no_rs', (int)$row['unit_kerja_kode'])->value('no_rs');
                if (!$unitKerjaId) {
                    $warnings[] = "unit_kerja_kode/no_rs {$row['unit_kerja_kode']} tidak ditemukan (baris ".($idx+2).")";
                }
            } elseif (!empty($row['unit_kerja_nama'])) {
                // Ambil yang pertama bila ada duplikat nama (beri warning)
                $list = Rumahsakit::where('nama_rumahsakit', $row['unit_kerja_nama'])->limit(2)->get(['no_rs','nama_rumahsakit']);
                if ($list->count() === 1) {
                    $unitKerjaId = $list[0]->no_rs;
                } elseif ($list->count() > 1) {
                    $unitKerjaId = $list[0]->no_rs;
                    $warnings[]  = "Nama unit kerja '{$row['unit_kerja_nama']}' ambigu (baris ".($idx+2)."), ambil no_rs={$unitKerjaId}. Disarankan pakai kolom unit_kerja_id/no_rs.";
                } else {
                    $warnings[]  = "Nama unit kerja '{$row['unit_kerja_nama']}' tidak ditemukan (baris ".($idx+2).").";
                }
            }

            if (!$unitKerjaId) {
                $warnings[] = "Lewati baris ".($idx+2)." karena unit_kerja_id tidak resolve.";
                continue;
            }

            // --- Resolve Jenjang (id) ---
            $jenjangId = null;
            if (!empty($row['jenjang_id'])) {
                $jenjangId = (int) $row['jenjang_id'];
                if (!Jenjangjabatan::whereKey($jenjangId)->exists()) {
                    $warnings[] = "jenjang_id {$jenjangId} tidak ditemukan (baris ".($idx+2).")";
                    $jenjangId  = null;
                }
            } elseif (!empty($row['jenjang_nama'])) {
                $jenjangId = Jenjangjabatan::where('nama_jenjang', $row['jenjang_nama'])->value('id');
                if (!$jenjangId) {
                    $warnings[] = "jenjang_nama '{$row['jenjang_nama']}' tidak ditemukan (baris ".($idx+2).").";
                }
            }

            if (!$jenjangId) {
                $warnings[] = "Lewati baris ".($idx+2)." karena jenjang_id tidak resolve.";
                continue;
            }

            // --- Kuota & Tahun ---
            $kuota = null;
            if (($row['kuota'] ?? '') !== '') {
                $kuota = is_numeric($row['kuota']) ? (int)$row['kuota'] : null;
            }
            if ($kuota === null) {
                $warnings[] = "Lewati baris ".($idx+2)." karena kuota tidak valid.";
                continue;
            }

            $tahun = null;
            if (($row['tahun_formasi'] ?? '') !== '') {
                $tahun = (int) $row['tahun_formasi'];
            }
            // Tahun boleh kosong, tapi disarankan isi. Kalau kosong, upsert kunci akan tanpa tahun (lihat seeder).

            // --- Nama Formasi (default = nama jenjang) ---
            $namaFormasi = $row['nama_formasi'] ?? null;
            if (!$namaFormasi && !empty($row['jenjang_nama'])) {
                $namaFormasi = $row['jenjang_nama'];
            } elseif (!$namaFormasi) {
                // fallback namaFormasi dari jenjang_id
                $namaFormasi = Jenjangjabatan::whereKey($jenjangId)->value('nama_jenjang') ?? 'Formasi';
            }

            // --- Build record ---
            $item = [
                'nama_formasi'  => $namaFormasi,
                'jenjang_id'    => $jenjangId,
                'unit_kerja_id' => $unitKerjaId,
                'kuota'         => $kuota,
                'tahun_formasi' => $tahun,
                'created_at'    => now()->toDateTimeString(),
                'updated_at'    => now()->toDateTimeString(),
            ];

            $data[] = $item;
            $processed++;
        }

        if (!count($data)) {
            $this->warn('Tidak ada baris valid untuk dibuatkan seeder.');
            return self::SUCCESS;
        }

        // Nama class seeder
        $class = $this->option('class') ?: ('FormasiFromExcelSeeder'.now()->format('YmdHis'));
        $file  = base_path("database/seeders/{$class}.php");

        // Export array ke PHP literal
        $phpArray = var_export($data, true);

        // Seeder content: upsert idempotent by (unit_kerja_id, jenjang_id, [tahun_formasi])
        $stub = <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use App\\Models\\Formasijabatan;

class {$class} extends Seeder
{
    public function run(): void
    {
        \$rows = {$phpArray};

        foreach (array_chunk(\$rows, 500) as \$chunk) {
            foreach (\$chunk as \$row) {
                // Jika tahun_formasi ada -> jadikan bagian kunci unik
                \$keys = [
                    'unit_kerja_id' => \$row['unit_kerja_id'],
                    'jenjang_id'    => \$row['jenjang_id'],
                ];
                if (!is_null(\$row['tahun_formasi'])) {
                    \$keys['tahun_formasi'] = \$row['tahun_formasi'];
                }

                Formasijabatan::updateOrCreate(\$keys, \$row);
            }
        }
    }
}

PHP;

        File::put($file, $stub);
        $this->info("Seeder dibuat: database/seeders/{$class}.php");
        $this->line("Jalankan: php artisan db:seed --class={$class}");

        // Tampilkan warning (jika ada)
        if (!empty($warnings)) {
            $this->newLine();
            $this->warn('PERINGATAN / PERLU DITINJAU:');
            foreach ($warnings as $w) $this->line(" - ".$w);
        }

        return self::SUCCESS;
    }
}
