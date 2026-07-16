<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnitKerjaTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UnitKerjaPetunjukSheet(),
            new UnitKerjaDataSheet(),
        ];
    }
}

// ─── Sheet 1: Petunjuk ────────────────────────────────────────────────────────
class UnitKerjaPetunjukSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Petunjuk'; }

    public function array(): array
    {
        return [
            ['Template Import Unit Kerja SMART JFT'],
            [''],
            ['PETUNJUK PENGISIAN'],
            [''],
            ['Kolom', 'Nama Header', 'Keterangan', 'Contoh / Nilai Valid'],
            ['A', 'nama_unit_kerja', 'Nama unit kerja (wajib)', 'Balai Pengujian Kendaraan Bermotor Jakarta'],
            ['B', 'alamat', 'Alamat lengkap unit kerja (opsional)', 'Jl. Merdeka No. 1, Jakarta Pusat'],
            ['C', 'no_telp', 'Nomor telepon unit kerja (opsional)', '021-1234567'],
            ['D', 'provinsi', 'Nama provinsi (opsional — hanya membantu memastikan Kab/Kota yang benar jika ada nama kembar antar provinsi)', 'DKI Jakarta'],
            ['E', 'kab_kota', 'Nama Kabupaten/Kota (wajib) — harus sama dengan master data wilayah. Boleh diawali "Kota " atau "Kabupaten " untuk membantu memilih jika nama kembar', 'Kota Jakarta Pusat'],
            ['F', 'matra', 'Matra unit kerja (wajib)', 'Darat / Laut / Udara / Kereta'],
            ['G', 'instansi', 'Jenis instansi (wajib)', 'Pusat / Daerah'],
            ['H', 'latitude', 'Koordinat lintang (opsional, angka desimal)', '-6.200000'],
            ['I', 'longitude', 'Koordinat bujur (opsional, angka desimal)', '106.816666'],
            [''],
            ['CATATAN PENTING'],
            ['1. Format file yang diterima: .xlsx atau .xls'],
            ['2. Jangan ubah nama kolom header di sheet "Data Unit Kerja"'],
            ['3. Baris dengan error akan diskip, baris valid tetap diimport'],
            ['4. Nama Kabupaten/Kota harus persis sama dengan master data wilayah di sistem — kalau ragu, cek dulu di form "Tambah Unit Kerja" pada dropdown Kab/Kota'],
            ['5. Jika nama Kab/Kota ada di lebih dari satu provinsi (jarang terjadi) atau ambigu Kota/Kabupaten, isi kolom Provinsi dan/atau awali dengan "Kota "/"Kabupaten " agar tidak salah pilih'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        $sheet->getStyle('A5:D5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);

        $sheet->getStyle('A15')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 22, 'C' => 60, 'D' => 45];
    }
}

// ─── Sheet 2: Data Unit Kerja (Template) ──────────────────────────────────────
class UnitKerjaDataSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Data Unit Kerja'; }

    public function array(): array
    {
        return [
            // Header
            ['nama_unit_kerja', 'alamat', 'no_telp', 'provinsi', 'kab_kota', 'matra', 'instansi', 'latitude', 'longitude'],
            // Contoh 1
            ['Balai Pengujian Kendaraan Bermotor Jakarta', 'Jl. Merdeka No. 1', '021-1234567', 'DKI Jakarta', 'Kota Jakarta Pusat', 'Darat', 'Pusat', '-6.186486', '106.834091'],
            // Contoh 2
            ['Kantor Kesyahbandaran Utama Tanjung Priok', 'Jl. Enggano No. 1', '021-4301080', 'DKI Jakarta', 'Kota Jakarta Utara', 'Laut', 'Pusat', '-6.104312', '106.881729'],
            // Contoh 3
            ['UPT Perkeretaapian Bandung', 'Jl. Stasiun Timur No. 1', '022-4200999', 'Jawa Barat', 'Kota Bandung', 'Kereta', 'Daerah', '-6.914744', '107.609810'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getStyle('A2:I4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);

        $sheet->getStyle('A1:I4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFBFBFBF'],
                ],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40, 'B' => 30, 'C' => 16, 'D' => 16, 'E' => 22,
            'F' => 12, 'G' => 12, 'H' => 14, 'I' => 14,
        ];
    }
}
