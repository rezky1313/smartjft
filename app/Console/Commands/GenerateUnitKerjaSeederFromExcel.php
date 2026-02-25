<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Rumahsakit;

class GenerateUnitKerjaSeederFromExcel extends Command
{
    protected $signature = 'unitkerja:make-seeder-from-excel
        {--file= : Path file Excel/CSV, contoh: storage/app/import/unitkerja.xlsx}
        {--class= : Nama class seeder (opsional)}
        {--sheet=0 : Index sheet (0 = pertama)}';

    protected $description = 'Baca Excel/CSV Unit Kerja dan generate seeder PHP di database/seeders/';

    /**
     * Alias header Excel -> key internal
     */
    protected array $headerAlias = [
        // nama unit
        'nama_unit'         => 'nama_rumahsakit',
        'nama_unit_kerja'   => 'nama_rumahsakit',
        'nama_rumahsakit'   => 'nama_rumahsakit',
        'unit_kerja'        => 'nama_rumahsakit',

        // alamat & telp
        'alamat'            => 'alamat',
        'no_telp'           => 'no_telp',
        'telp'              => 'no_telp',
        'telepon'           => 'no_telp',

        // wilayah
        'provinsi'          => 'province_name',
        'province'          => 'province_name',
        'kab_kota'          => 'regency_name',
        'kab/kota'          => 'regency_name', // <— Excel kamu pakai ini
        'kabupaten_kota'    => 'regency_name',
        'kota_kab'          => 'regency_name',
        'kota/kab'          => 'regency_name',
        'tipe'              => 'regency_type',
        'type'              => 'regency_type',
        'regency_id'        => 'regency_id',

        // koordinat
        'latitude'          => 'latitude',
        'lat'               => 'latitude',
        'longitude'         => 'longitude',
        'long'              => 'longitude',
        'lng'               => 'longitude',

        // tambahan
        'matra'             => 'matra',
        'instansi'          => 'instansi',

        // kode/no_rs (opsional)
        'no_rs'             => 'no_rs',
        'unit_kerja_kode'   => 'no_rs',
        'kode_unit'         => 'no_rs',
    ];

    protected array $allowedMatra    = ['DARAT','LAUT','UDARA','KERETA'];
    protected array $allowedInstansi = ['PUSAT','DAERAH'];

    /** Normalisasi nama provinsi yang sering beda penulisan */
    protected array $provinceAliases = [
        'nanggroe aceh darussalam' => 'Aceh',
        'daerah istimewa yogyakarta' => 'DI Yogyakarta',
        'dki jakarta' => 'DKI Jakarta',
        'kepulauan bangka belitung' => 'Kepulauan Bangka Belitung',
        'kepulauan riau' => 'Kepulauan Riau',
        'papua barat daya' => 'Papua Barat Daya',
        'papua pegunungan' => 'Papua Pegunungan',
        'papua tengah' => 'Papua Tengah',
        'papua selatan' => 'Papua Selatan',
        // tambahkan bila perlu
    ];

    public function handle(): int
    {
        $path       = $this->option('file') ?: 'storage/app/import/unitkerja.xlsx';
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

        // --- Baca header & petakan dengan alias ---
        $rawHeader = array_shift($rows);
        $headers   = []; // ex: 'A' => 'nama_rumahsakit'
        foreach ($rawHeader as $col => $name) {
            $norm = Str::snake(Str::lower((string)$name)); // "Kab/Kota" -> "kab_kota" (slash jadi underscore oleh snake)
            // snake("kab/kota") -> "kab_kota", kita tangani juga yang persis "kab/kota"
            if ($norm === 'kab_kota' && !isset($this->headerAlias[$norm]) && isset($this->headerAlias['kab/kota'])) {
                $headers[$col] = $this->headerAlias['kab/kota'];
                continue;
            }
            if (isset($this->headerAlias[$norm])) {
                $headers[$col] = $this->headerAlias[$norm];
            }
        }

        if (!in_array('nama_rumahsakit', $headers, true)) {
            $this->error('Header "Nama Unit Kerja" tidak ditemukan. Gunakan salah satu: nama_unit / nama_unit_kerja / nama_rumahsakit / unit_kerja.');
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

            // Skip baris kosong
            if (!($row['nama_rumahsakit'] ?? null)) continue;

            // --- Resolve Regency ---
            $regencyId = null;

            // a) langsung dari regency_id kalau ada
            if (!empty($row['regency_id'])) {
                $regencyId = Regency::whereKey((int)$row['regency_id'])->value('id');
                if (!$regencyId) {
                    $warnings[] = "regency_id tidak valid ({$row['regency_id']}) pada baris ".($idx+2);
                }
            } else {
                // b) pakai Provinsi + Kab/Kota (tanpa kolom tipe)
                $provinceName = $this->normalizeProvinceName($row['province_name'] ?? null);
                $regencyRaw   = $row['regency_name'] ?? null;
                $regencyId    = $this->resolveRegencyId($provinceName, $regencyRaw, $warnings, $idx+2);
            }

            if (!$regencyId) {
                $warnings[] = "Lewati baris ".($idx+2)." karena regency_id tidak resolve.";
                continue;
            }

            // --- Normalisasi Matra ---
            $matra = null;
            if (!empty($row['matra'])) {
                $m = Str::upper($row['matra']);
                if (Str::contains($m, 'DARAT')) $m = 'DARAT';
                elseif (Str::contains($m, 'LAUT')) $m = 'LAUT';
                elseif (Str::contains($m, 'UDARA') || Str::contains($m, 'AIR')) $m = 'UDARA';
                elseif (Str::contains($m, 'KERETA') || Str::contains($m, 'RAIL')) $m = 'KERETA';
                $matra = in_array($m, $this->allowedMatra, true) ? Str::title(Str::lower($m)) : null;
            }

            // --- Normalisasi Instansi ---
            $instansi = null;
            if (!empty($row['instansi'])) {
                $i = Str::upper($row['instansi']);
                $i = Str::contains($i,'PUSAT') ? 'PUSAT' : (Str::contains($i,'DAERAH') ? 'DAERAH' : null);
                $instansi = $i ? Str::title(Str::lower($i)) : null;
            }

            // --- Koordinat ---
            $lat = null; $lng = null;
            if (($row['latitude'] ?? '') !== '') {
                $lat = is_numeric($row['latitude']) ? (float)$row['latitude'] : null;
            }
            if (($row['longitude'] ?? '') !== '') {
                $lng = is_numeric($row['longitude']) ? (float)$row['longitude'] : null;
            }

            // --- Build record ---
            $item = [
                'nama_rumahsakit' => $row['nama_rumahsakit'] ?? null,
                'alamat'          => $row['alamat'] ?? null,
                'no_telp'         => $row['no_telp'] ?? null,
                'regency_id'      => $regencyId,
                'latitude'        => $lat,
                'longitude'       => $lng,
                'matra'           => $matra,
                'instansi'        => $instansi,
                'created_at'      => now()->toDateTimeString(),
                'updated_at'      => now()->toDateTimeString(),
            ];

            $data[] = $item;
            $processed++;
        }

        if (!count($data)) {
            $this->warn('Tidak ada baris valid untuk dibuatkan seeder.');
            return self::SUCCESS;
        }

        // Nama class seeder
        $class = $this->option('class') ?: ('UnitKerjaFromExcelSeeder'.now()->format('YmdHis'));
        $file  = base_path("database/seeders/{$class}.php");

        // Export array ke PHP literal
        $phpArray = var_export($data, true);

        // Seeder content
        $stub = <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use App\\Models\\Rumahsakit;

class {$class} extends Seeder
{
    public function run(): void
    {
        \$rows = {$phpArray};

        // Upsert idempotent berdasarkan kombinasi (nama_rumahsakit, regency_id)
        foreach (array_chunk(\$rows, 500) as \$chunk) {
            foreach (\$chunk as \$row) {
                Rumahsakit::updateOrCreate(
                    [
                        'nama_rumahsakit' => \$row['nama_rumahsakit'],
                        'regency_id'      => \$row['regency_id'],
                    ],
                    \$row
                );
            }
        }
    }
}

PHP;

        File::put($file, $stub);
        $this->info("Seeder dibuat: database/seeders/{$class}.php");
        $this->line("Jalankan: php artisan db:seed --class={$class}");

        if (count($warnings)) {
            $this->newLine();
            $this->warn('PERINGATAN / PERLU DITINJAU:');
            foreach ($warnings as $w) $this->line(" - ".$w);
        }

        return self::SUCCESS;
    }

    /** Normalisasi nama provinsi agar match tabel provinces */
    private function normalizeProvinceName(?string $name): ?string
    {
        if (!$name) return null;
        $n = trim($name);
        $key = Str::lower($n);
        if (isset($this->provinceAliases[$key])) {
            return $this->provinceAliases[$key];
        }
        // Rapikan kapital
        // Tangani "dki jakarta" -> "DKI Jakarta", "di yogyakarta" -> "DI Yogyakarta"
        if (preg_match('/^(dki|di)\s+/i', $n)) {
            $parts = preg_split('/\s+/', $n, 2);
            return Str::upper($parts[0]).' '.Str::title($parts[1] ?? '');
        }
        return Str::title($n);
    }

    /** Resolve regency_id dari nama provinsi + kab/kota (tanpa kolom tipe) */
    private function resolveRegencyId(?string $provinceName, ?string $regencyRaw, array &$warnings, int $excelRowNumber): ?int
    {
        if (!$regencyRaw) return null;

        // Deteksi hint tipe dari teks (opsional)
        $typeHint = null;
        $name = trim($regencyRaw);
        $lower = Str::lower($name);
        if (Str::startsWith($lower, 'kota ')) {
            $typeHint = 'KOTA';
            $name = trim(Str::after($name, ' '));
        } elseif (Str::startsWith($lower, 'kabupaten ')) {
            $typeHint = 'KABUPATEN';
            $name = trim(Str::after($name, ' '));
        }

        // Filter by provinsi (kalau ada)
        $q = Regency::query()->where('name', $name);
        if ($provinceName) {
            $provinceId = Province::where('name', $provinceName)->value('id');
            if ($provinceId) $q->where('province_id', $provinceId);
        }

        $list = $q->get(['id','name','type','province_id']);

        if ($list->count() === 1) {
            return $list[0]->id;
        }

        if ($list->count() > 1) {
            // Coba pakai hint dari teks (jika Excel menulis "Kota …" / "Kabupaten …")
            if ($typeHint) {
                $found = $list->firstWhere('type', $typeHint);
                if ($found) return $found->id;
            }
            // Tanpa hint: default pilih KOTA, dan beri warning
            $found = $list->firstWhere('type', 'KOTA') ?: $list->first();
            $warnings[] = "Kab/Kota ambigu untuk '{$regencyRaw}' pada baris {$excelRowNumber}, memilih id={$found->id} (type={$found->type}). Disarankan tulis 'Kota ...' atau 'Kabupaten ...' di Excel untuk yang ambigu.";
            return $found->id;
        }

        // Tidak ketemu sama sekali
        $warnings[] = "Kab/Kota tidak ditemukan '{$regencyRaw}' pada baris {$excelRowNumber}.";
        return null;
    }
}
