<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\FormulaRfMaster;

/**
 * Import satu-kali rumus Kebutuhan Formasi JF Penguji Kendaraan Bermotor (PKB)
 * dari file referensi Excel (database/seeders/data/formula_rf_pkb_referensi.xlsx)
 * ke tabel formula_rf_master.
 *
 * Layout kolom per sheet DITEMUKAN LEWAT INSPEKSI LANGSUNG file referensi asli
 * (bukan cuma diasumsikan dari deskripsi) -- lihat laporan ekstraksi yang
 * ditampilkan command ini setelah jalan, untuk cross-check manual.
 *
 * Hasil ekstraksi diverifikasi SAMPAI MATCH PERSIS terhadap kolom Wpv (L/M,
 * "Waktu Penyelesaian x Volume") yang sudah dihitung Excel sendiri, untuk
 * seluruh 4 sheet, memakai data Kabupaten Bandung dari Sheet1 -- lihat RF-1B.
 */
class ImportFormulaRfPkb extends Command
{
    protected $signature = 'formula-rf:import-pkb';

    protected $description = 'Import rumus Kebutuhan Formasi JF PKB dari Excel referensi ke formula_rf_master';

    /**
     * Konfigurasi kolom per sheet, hasil inspeksi manual terhadap file referensi asli.
     * unsurCol/subUnsurCol = kolom yang nilainya merge/lanjut ke bawah (carry-forward).
     * butirCols = kandidat kolom teks butir kegiatan, dicek dari KANAN ke KIRI --
     *   dipakai kolom pertama (paling kanan) yang berisi teks asli (bukan sekadar
     *   angka index sub-item seperti "1", "2", "3").
     * jamCol = kolom helper "waktu dalam jam" (biasanya "=waktuCol/60"). Pada 2 baris
     *   di TERAMPIL_DISHUB, kolom ini berisi SUM(...) yang mengagregasi jam dari
     *   beberapa baris child sekaligus -- baris seperti ini TETAP baris butir
     *   kegiatan nyata (representasi 1 aktivitas "meliputi: ..." beranak beberapa
     *   sub-item), meskipun kolom waktuCol mentahnya sendiri kosong.
     */
    protected array $sheetConfig = [
        'pemula' => [
            'sheet' => 'PEMULA_DISHUB',
            'unsurCol' => 'B', 'subUnsurCol' => 'D',
            'butirCols' => ['E', 'F'],
            'satuanCol' => 'G', 'akCol' => 'H', 'waktuCol' => 'I', 'jamCol' => 'J', 'volumeCol' => 'K',
            'startRow' => 7, 'endRow' => 30,
            'defaultSatuanUnit' => false,
        ],
        'terampil' => [
            'sheet' => 'TERAMPIL_DISHUB',
            'unsurCol' => 'B', 'subUnsurCol' => 'D',
            'butirCols' => ['E', 'F'],
            'satuanCol' => 'G', 'akCol' => 'H', 'waktuCol' => 'I', 'jamCol' => 'J', 'volumeCol' => 'K',
            'startRow' => 8, 'endRow' => 112,
            'defaultSatuanUnit' => false,
        ],
        'mahir' => [
            'sheet' => 'MAHIR_DISHUB',
            'unsurCol' => 'C', 'subUnsurCol' => 'E',
            'butirCols' => ['F', 'G'],
            'satuanCol' => 'H', 'akCol' => 'I', 'waktuCol' => 'J', 'jamCol' => 'K', 'volumeCol' => 'L',
            'startRow' => 8, 'endRow' => 53,
            'defaultSatuanUnit' => false,
        ],
        'penyelia' => [
            'sheet' => 'PENYELIA_DISHUB',
            'unsurCol' => 'C', 'subUnsurCol' => 'E',
            'butirCols' => ['F', 'G'],
            'satuanCol' => 'H', 'akCol' => 'I', 'waktuCol' => 'J', 'jamCol' => 'K', 'volumeCol' => 'L',
            'startRow' => 8, 'endRow' => 45,
            'defaultSatuanUnit' => true, // satuan sering kosong di sheet ini -> default 'Unit'
        ],
    ];

    /** Baris tanpa formula Volume di Excel -- sumber_volume disimpan NULL, kontribusi 0 (utk laporan). */
    protected array $volumeKosongRows = [];

    /** Baris yang waktu ada tapi Angka Kredit kosong (utk laporan, bukan error). */
    protected array $akKosongRows = [];

    /** Baris "agregat" (waktu dari SUM(...) beberapa child, bukan dari sel waktu tunggal). */
    protected array $agregatRows = [];

    /** Hasil ekstraksi lengkap per jenjang, utk ditampilkan sebagai rekap. */
    protected array $extractedRows = [];

    public function handle(): int
    {
        $path = database_path('seeders/data/formula_rf_pkb_referensi.xlsx');

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $this->info('Membaca file: ' . $path);
        $spreadsheet = IOFactory::load($path);

        $allRecords = [];

        foreach ($this->sheetConfig as $jenjang => $cfg) {
            $sheet = $spreadsheet->getSheetByName($cfg['sheet']);
            if (!$sheet) {
                $this->error("Sheet {$cfg['sheet']} tidak ditemukan, dilewati.");
                continue;
            }

            $volumeMap = $this->buildVolumeResolutionMap($sheet, $cfg);
            $rows = $this->extractRows($sheet, $cfg, $jenjang, $volumeMap);

            $this->extractedRows[$jenjang] = $rows;
            $allRecords = array_merge($allRecords, $rows);

            $this->info(ucfirst($jenjang) . ': ' . count($rows) . ' butir kegiatan diekstrak dari sheet ' . $cfg['sheet']);
        }

        DB::transaction(function () use ($allRecords) {
            FormulaRfMaster::where('kode_jf', 'pkb')->delete();

            foreach ($allRecords as $record) {
                FormulaRfMaster::create($record);
            }
        });

        $this->newLine();
        $this->info('Total baris disimpan: ' . count($allRecords));

        if (!empty($this->agregatRows)) {
            $this->newLine();
            $this->warn('BARIS AGREGAT (waktu dari SUM(...) beberapa child sekaligus, BUKAN sel waktu tunggal):');
            foreach ($this->agregatRows as $r) {
                $this->line("  - [{$r['jenjang']}] baris Excel {$r['row']}: \"{$r['butir_kegiatan']}\" (waktu={$r['waktu_menit']} menit)");
            }
        }

        if (!empty($this->volumeKosongRows)) {
            $this->newLine();
            $this->warn('BARIS TANPA FORMULA VOLUME (sumber_volume=NULL, kontribusi 0 -- BUKAN error, sudah divalidasi terhadap kolom Wpv Excel):');
            foreach ($this->volumeKosongRows as $r) {
                $this->line("  - [{$r['jenjang']}] baris Excel {$r['row']}: \"{$r['butir_kegiatan']}\"");
            }
        }

        if (!empty($this->akKosongRows)) {
            $this->newLine();
            $this->warn('BARIS DENGAN ANGKA KREDIT KOSONG (waktu tetap ada & tetap diimpor, AK hanya dokumentasi):');
            foreach ($this->akKosongRows as $r) {
                $this->line("  - [{$r['jenjang']}] baris Excel {$r['row']}: \"{$r['butir_kegiatan']}\"");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Pass 1: scan SELURUH baris di kolom volume (termasuk baris yang nanti
     * di-skip dari output final, misal baris header sub-unsur) supaya referensi
     * diri (misal "=K21") ke baris tersebut tetap bisa di-resolve.
     *
     * @return array<string, array{sumber_volume: ?string, konstanta: ?float}>
     */
    protected function buildVolumeResolutionMap(Worksheet $sheet, array $cfg): array
    {
        $map = [];
        $col = $cfg['volumeCol'];

        for ($row = $cfg['startRow']; $row <= $cfg['endRow']; $row++) {
            $raw = $sheet->getCell($col . $row)->getValue();
            $map[$col . $row] = $this->resolveSumberVolume($raw, $map);
        }

        return $map;
    }

    /**
     * Tentukan sumber_volume (+ nilai konstanta literal bila relevan) dari isi
     * mentah 1 sel kolom Volume.
     *
     * @return array{sumber_volume: ?string, konstanta: ?float}
     */
    protected function resolveSumberVolume($raw, array $map): array
    {
        $empty = ['sumber_volume' => null, 'konstanta' => null];

        if ($raw === null || $raw === '') {
            return $empty;
        }

        // Angka literal langsung (bukan formula), misal 240 -- SIMPAN nilai
        // aslinya, jangan diasumsikan selalu 240 (terverifikasi: 1 baris Mahir
        // literalnya 10, beda dari 14 baris lain yang literalnya 240).
        if (is_numeric($raw)) {
            return ['sumber_volume' => 'konstanta_hari_kerja', 'konstanta' => (float) $raw];
        }

        if (!is_string($raw) || $raw[0] !== '=') {
            return $empty;
        }

        $expr = ltrim($raw, '=');

        if (stripos($expr, 'Sheet1!F4') !== false) return ['sumber_volume' => 'uji_pertama', 'konstanta' => null];
        if (stripos($expr, 'Sheet1!G4') !== false) return ['sumber_volume' => 'uji_reguler', 'konstanta' => null];
        if (stripos($expr, 'Sheet1!E4') !== false) return ['sumber_volume' => 'kb_diuji_total', 'konstanta' => null];
        if (stripos($expr, 'Sheet1!S4') !== false) return ['sumber_volume' => 'bbm_bensin', 'konstanta' => null];
        if (stripos($expr, 'Sheet1!T4') !== false) return ['sumber_volume' => 'bbm_solar', 'konstanta' => null];

        // Referensi ke sel lain di sheet yang sama, misal "K11", "L13"
        if (preg_match('/^([A-Z]+)(\d+)$/', $expr, $m)) {
            $key = $m[1] . $m[2];
            return $map[$key] ?? $empty;
        }

        // Formula aritmatika murni tanpa referensi sel, misal "20*12"
        if (preg_match('/^[\d\.\s\*\+\-\/\(\)]+$/', $expr)) {
            $konstanta = (float) \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance()
                ->calculateFormula('=' . $expr);
            return ['sumber_volume' => 'konstanta_hari_kerja', 'konstanta' => $konstanta];
        }

        return $empty;
    }

    /**
     * Pass 2: ekstrak baris butir kegiatan nyata dari sheet, dengan carry-forward
     * unsur/sub_unsur. Sumber volume murni hasil resolusi PER-BARIS, tanpa fallback.
     */
    protected function extractRows(Worksheet $sheet, array $cfg, string $jenjang, array $volumeMap): array
    {
        $records = [];
        $unsur = null;
        $subUnsur = null;
        $urutan = 0;

        for ($row = $cfg['startRow']; $row <= $cfg['endRow']; $row++) {
            $unsurVal = $this->cellText($sheet, $cfg['unsurCol'], $row);
            if ($unsurVal !== null) {
                $unsur = $unsurVal;
            }

            $subUnsurVal = $this->cellText($sheet, $cfg['subUnsurCol'], $row);
            if ($subUnsurVal !== null) {
                $subUnsur = $subUnsurVal;
            }

            $butir = $this->extractButirKegiatan($sheet, $cfg['butirCols'], $row);
            if ($butir === null) {
                continue; // bukan baris butir kegiatan (header/kosong)
            }

            // Waktu SELALU diambil dari nilai TERHITUNG kolom helper "jam" (mis. J
            // pada Pemula/Terampil), bukan dibaca mentah dari kolom waktu (I). Ini
            // penting: pada Terampil baris 14, kolom I menampilkan 1.5 tapi formula
            // J-nya di-hardcode "=1/60" (bukan "=I14/60", ketidakkonsistenan input
            // manual di file sumber) -- Excel sendiri MEMAKAI hasil J itu (bukan I)
            // untuk menghitung Wpv (L=J*K), jadi sistem ini ikut J supaya match
            // persis dengan angka resmi Excel. Pola ini juga otomatis menangani 2
            // baris agregat "... meliputi:" di TERAMPIL_DISHUB, yang J-nya berupa
            // SUM(...) beberapa child sekaligus alih-alih sel waktu tunggal.
            $jamRaw = $sheet->getCell($cfg['jamCol'] . $row)->getValue();
            $isAgregat = is_string($jamRaw) && stripos($jamRaw, 'SUM(') !== false;

            // Baris "header" (bukan butir kegiatan nyata) biasanya punya formula jam
            // "=waktuCol/60" yang menunjuk ke sel waktuCol yang KOSONG -- itu
            // terhitung 0 di Excel (bukan kosong), jadi is_numeric() saja tidak
            // cukup untuk membedakannya dari butir kegiatan sungguhan bernilai 0.
            // Baris dianggap valid HANYA jika waktuCol mentah terisi (kasus normal)
            // ATAU baris ini memang baris agregat SUM (2 kasus di TERAMPIL_DISHUB).
            $waktuRawFilled = $this->parseDecimal($sheet->getCell($cfg['waktuCol'] . $row)->getValue()) !== null;
            if (!$waktuRawFilled && !$isAgregat) {
                continue; // baris header/kosong, bukan butir kegiatan -> skip
            }

            $jamCalc = $sheet->getCell($cfg['jamCol'] . $row)->getCalculatedValue();
            if (!is_numeric($jamCalc)) {
                continue; // tanpa waktu sama sekali, tidak bisa dipakai kalkulasi -> skip
            }

            $waktu = round(((float) $jamCalc) * 60, 4); // jam -> menit

            $akRaw = $sheet->getCell($cfg['akCol'] . $row)->getValue();
            $ak = $this->parseDecimal($akRaw);
            if ($ak === null) {
                $this->akKosongRows[] = [
                    'jenjang' => $jenjang,
                    'row' => $row,
                    'butir_kegiatan' => $butir,
                ];
            }

            $satuan = $this->cellText($sheet, $cfg['satuanCol'], $row);
            if ($satuan === null && !empty($cfg['defaultSatuanUnit'])) {
                $satuan = 'Unit';
            }

            $volumeKey = $cfg['volumeCol'] . $row;
            $resolved = $volumeMap[$volumeKey] ?? ['sumber_volume' => null, 'konstanta' => null];
            $sumberVolume = $resolved['sumber_volume'];
            $volumeKonstanta = $resolved['konstanta'];

            if ($sumberVolume === null) {
                $this->volumeKosongRows[] = [
                    'jenjang' => $jenjang,
                    'row' => $row,
                    'butir_kegiatan' => $butir,
                ];
            }

            if ($isAgregat) {
                $this->agregatRows[] = [
                    'jenjang' => $jenjang,
                    'row' => $row,
                    'butir_kegiatan' => $butir,
                    'waktu_menit' => $waktu,
                ];
            }

            $urutan++;

            $records[] = [
                'kode_jf' => 'pkb',
                'jenjang' => $jenjang,
                'unsur' => $unsur ?? '-',
                'sub_unsur' => $subUnsur,
                'butir_kegiatan' => $butir,
                'satuan_hasil' => $satuan,
                'angka_kredit' => $ak,
                'waktu_menit' => $waktu,
                'sumber_volume' => $sumberVolume,
                'volume_konstanta' => $volumeKonstanta,
                'urutan' => $urutan,
            ];
        }

        return $records;
    }

    /**
     * Ambil teks butir kegiatan dari kandidat kolom (dicek KANAN ke KIRI).
     * Kolom yang cuma berisi angka index sub-item (misal "1", "2") dilewati,
     * karena bukan teks butir kegiatan sungguhan -- teksnya ada di kolom
     * berikutnya (satu kolom kanan dari kolom angka index tersebut).
     */
    protected function extractButirKegiatan(Worksheet $sheet, array $butirCols, int $row): ?string
    {
        foreach (array_reverse($butirCols) as $col) {
            $text = $this->cellText($sheet, $col, $row);
            if ($text !== null && !is_numeric($text) && mb_strlen($text) > 2) {
                return $text;
            }
        }

        return null;
    }

    /**
     * Ambil nilai sel sebagai teks (trim), null kalau kosong.
     */
    protected function cellText(Worksheet $sheet, string $col, int $row): ?string
    {
        $value = $sheet->getCell($col . $row)->getValue();

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Parse angka yang mungkin memakai koma sebagai desimal (format Indonesia),
     * misal "0,00050" -> 0.00050.
     */
    protected function parseDecimal($raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        if (is_string($raw)) {
            $normalized = str_replace(',', '.', trim($raw));
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return null;
    }
}
