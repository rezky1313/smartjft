<?php

namespace App\Exports;

use App\Models\UnitKerja;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FormasiTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new FormasiPetunjukSheet(),
            new FormasiDataSheet(),
        ];
    }
}

// ─── Sheet 1: Petunjuk ────────────────────────────────────────────────────────
class FormasiPetunjukSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Petunjuk'; }

    public function array(): array
    {
        $contohUnit = UnitKerja::orderBy('nama_unit_kerja')->limit(3)->pluck('nama_unit_kerja')->all();

        $rows = [
            ['Template Import Formasi SMART JFT'],
            [''],
            ['PETUNJUK PENGISIAN'],
            [''],
            ['PENTING: Format Formasi berbeda dari Bank Soal / Pegawai — formatnya PIVOT (satu baris = satu Jabatan di satu Unit Kerja, kolom kuota terpisah per jenjang), BUKAN satu baris per formasi.'],
            [''],
            ['Kolom', 'Nama Header', 'Keterangan', 'Contoh / Nilai Valid'],
            ['A', 'Nama Unit Kerja', 'Nama unit kerja (wajib) — HARUS SAMA PERSIS dengan data di menu Unit Kerja. Boleh diulang di tiap baris atau dikosongkan pada baris lanjutan (mengikuti baris terakhir yang terisi)', 'Balai Pengujian Kendaraan Bermotor Jakarta'],
            ['B', 'Nama Jabatan', 'Nama jabatan fungsional (wajib)', 'Penguji Kendaraan Bermotor'],
            ['C', 'Pemula', 'Kuota jenjang Pemula (angka, kosongkan/0 jika tidak ada)', '2'],
            ['D', 'Terampil', 'Kuota jenjang Terampil', '3'],
            ['E', 'Mahir', 'Kuota jenjang Mahir', '2'],
            ['F', 'Penyelia', 'Kuota jenjang Penyelia', '1'],
            ['G', 'Ahli Pertama', 'Kuota jenjang Ahli Pertama', '2'],
            ['H', 'Ahli Muda', 'Kuota jenjang Ahli Muda', '1'],
            ['I', 'Ahli Madya', 'Kuota jenjang Ahli Madya', '0'],
            ['J', 'Ahli Utama', 'Kuota jenjang Ahli Utama', '0'],
            [''],
            ['CARA MENDAPATKAN NAMA UNIT KERJA YANG VALID'],
            ['1. Buka menu Unit Kerja, salin nama persis seperti yang tertulis di kolom "Nama Unit Kerja"'],
            ['2. Contoh nama unit kerja yang terdaftar saat ini: ' . (count($contohUnit) ? implode('; ', $contohUnit) : '(belum ada data Unit Kerja)')],
            [''],
            ['CATATAN PENTING'],
            ['1. Format file yang diterima: .xlsx atau .xls'],
            ['2. Jangan ubah nama kolom header di sheet "Data Formasi"'],
            ['3. Tahun formasi TIDAK diisi di file Excel — dipilih terpisah lewat field "Tahun Formasi" di form Import Excel'],
            ['4. Import dengan tahun yang SAMA dengan data existing akan meng-update kuota (baris lama yang tidak muncul lagi di file akan dihapus)'],
            ['5. Import dengan tahun BARU akan memindahkan (remap) pegawai dari formasi tahun lama ke formasi tahun baru yang sepadan'],
            ['6. Unit Kerja yang tidak ditemukan (nama tidak cocok) akan dilewati — cek pesan hasil import setelah upload'],
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);

        $sheet->mergeCells('A5:D5');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF9C0006']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFC7CE']],
        ]);

        $sheet->getStyle('A7:D7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);

        $sheet->getStyle('A17')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
        $sheet->getStyle('A21')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 20, 'C' => 60, 'D' => 40];
    }
}

// ─── Sheet 2: Data Formasi (Template) — format PIVOT ─────────────────────────
class FormasiDataSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Data Formasi'; }

    public function array(): array
    {
        $unit = UnitKerja::orderBy('nama_unit_kerja')->value('nama_unit_kerja') ?? 'Nama Unit Kerja Contoh';

        return [
            // Header
            ['Nama Unit Kerja', 'Nama Jabatan', 'Pemula', 'Terampil', 'Mahir', 'Penyelia', 'Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama'],
            // Contoh 1
            [$unit, 'Penguji Kendaraan Bermotor', 2, 3, 2, 1, 2, 1, 0, 0],
            // Contoh 2 — unit sama, jabatan lain (nama unit boleh dikosongkan, ikut baris di atasnya)
            ['', 'Pengawas Keselamatan Pelayaran', 0, 0, 0, 0, 1, 1, 1, 0],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getStyle('A2:J3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);

        $sheet->getStyle('A1:J3')->applyFromArray([
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
            'A' => 40, 'B' => 30, 'C' => 10, 'D' => 10, 'E' => 10, 'F' => 10,
            'G' => 13, 'H' => 12, 'I' => 12, 'J' => 12,
        ];
    }
}
