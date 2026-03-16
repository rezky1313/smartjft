<?php

/**
 * Helper Functions untuk SMART JFT
 */

if (!function_exists('toRoman')) {
    /**
     * Convert angka ke angka Romawi
     *
     * @param int $num Angka yang akan dikonversi (1-3999)
     * @return string Angka Romawi
     */
    function toRoman($num)
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($num >= $value) {
                $result .= $roman;
                $num -= $value;
            }
        }

        return $result;
    }
}

if (!function_exists('formatNomorPermohonanUjikom')) {
    /**
     * Generate nomor permohonan Uji Kompetensi
     * Format: UJIKOM/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT]
     * Contoh: UJIKOM/III/2026/001
     *
     * @param int $noUrut Nomor urut permohonan
     * @param string|null $tanggal Tanggal permohonan (Y-m-d) atau null untuk tanggal sekarang
     * @return string Nomor permohonan terformat
     */
    function formatNomorPermohonanUjikom($noUrut, $tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = date('Y-m-d');
        }

        $date = \Carbon\Carbon::parse($tanggal);
        $romawi = toRoman((int)$date->format('m'));
        $tahun = $date->format('Y');
        $noUrutFormatted = str_pad($noUrut, 3, '0', STR_PAD_LEFT);

        return "UJIKOM/{$romawi}/{$tahun}/{$noUrutFormatted}";
    }
}

if (!function_exists('formatNomorPermohonanPengangkatan')) {
    /**
     * Generate nomor permohonan Pertimbangan Pengangkatan
     * Format: PANGKAT/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT]
     * Contoh: PANGKAT/III/2026/0001
     *
     * @param int $noUrut Nomor urut permohonan
     * @param string|null $tanggal Tanggal permohonan (Y-m-d) atau null untuk tanggal sekarang
     * @return string Nomor permohonan terformat
     */
    function formatNomorPermohonanPengangkatan($noUrut, $tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = date('Y-m-d');
        }

        $date = \Carbon\Carbon::parse($tanggal);
        $romawi = toRoman((int)$date->format('m'));
        $tahun = $date->format('Y');
        $noUrutFormatted = str_pad($noUrut, 4, '0', STR_PAD_LEFT);

        return "PANGKAT/{$romawi}/{$tahun}/{$noUrutFormatted}";
    }
}

