<?php

namespace App\Exports;

use App\Models\SoalKategori;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BankSoalTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new BankSoalPetunjukSheet(),
            new BankSoalKategoriSheet(),
            new BankSoalDataSheet(),
        ];
    }
}

// ─── Sheet 1: Petunjuk ────────────────────────────────────────────────────────
class BankSoalPetunjukSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Petunjuk'; }

    public function array(): array
    {
        return [
            ['Template Import Bank Soal SMART JFT'],
            [''],
            ['PETUNJUK PENGISIAN'],
            [''],
            ['Kolom', 'Nama Header', 'Keterangan', 'Contoh / Nilai Valid'],
            ['A', 'jenis', 'Jenis soal', 'umum ATAU spesifik'],
            ['B', 'soal_kategori_id', 'ID kategori (lihat sheet Daftar Kategori). Kosongkan jika jenis = umum', '1'],
            ['C', 'pertanyaan', 'Teks pertanyaan soal (wajib)', 'Apa yang dimaksud dengan uji emisi kendaraan?'],
            ['D', 'pilihan_a', 'Teks pilihan jawaban A (wajib)', 'Pengujian kadar gas buang'],
            ['E', 'pilihan_b', 'Teks pilihan jawaban B (wajib)', 'Pengujian kelaikan rem'],
            ['F', 'pilihan_c', 'Teks pilihan jawaban C (wajib)', 'Pengujian lampu kendaraan'],
            ['G', 'pilihan_d', 'Teks pilihan jawaban D (wajib)', 'Pengujian kemudi kendaraan'],
            ['H', 'jawaban_benar', 'Kode jawaban yang benar', 'A ATAU B ATAU C ATAU D'],
            ['I', 'tingkat_kesulitan', 'Tingkat kesulitan soal', 'mudah ATAU sedang ATAU sulit'],
            ['J', 'taksonomi_bloom', 'Taksonomi Bloom', 'C1_mengingat / C2_memahami / C3_menerapkan / C4_menganalisis / C5_mengevaluasi / C6_mencipta'],
            ['K', 'pembahasan', 'Penjelasan jawaban benar (opsional)', 'Uji emisi adalah pengujian terhadap kadar...'],
            ['L', 'status', 'Status soal setelah diimport', 'draft ATAU aktif'],
            [''],
            ['CATATAN PENTING'],
            ['1. Maksimal 500 soal per file'],
            ['2. Format file yang diterima: .xlsx atau .xls'],
            ['3. Jangan ubah nama kolom header di sheet Data Soal'],
            ['4. Soal dengan error akan diskip, soal valid tetap diimport'],
            ['5. soal_kategori_id wajib diisi jika jenis = spesifik'],
            ['6. Lihat sheet "Daftar Kategori" untuk ID kategori yang tersedia'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Judul
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sub judul
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        // Header tabel petunjuk
        $sheet->getStyle('A5:D5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);

        // Catatan penting
        $sheet->getStyle('A19')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 22, 'C' => 55, 'D' => 70];
    }
}

// ─── Sheet 2: Daftar Kategori ─────────────────────────────────────────────────
class BankSoalKategoriSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Daftar Kategori'; }

    public function array(): array
    {
        $rows = [['ID Kategori', 'Nama Kategori', 'Jabatan', 'Jenjang', 'Matra', 'Bidang']];

        $kategoris = SoalKategori::where('aktif', true)->orderBy('id')->get();
        foreach ($kategoris as $k) {
            $rows[] = [
                $k->id,
                $k->nama,
                $k->jabatan ?? '-',
                $k->label_jenjang,
                $k->label_matra,
                $k->bidang ?? '-',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);
        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 35, 'C' => 35, 'D' => 16, 'E' => 10, 'F' => 14];
    }
}

// ─── Sheet 3: Data Soal (Template) ───────────────────────────────────────────
class BankSoalDataSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Data Soal'; }

    public function array(): array
    {
        return [
            // Header
            ['jenis', 'soal_kategori_id', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar', 'tingkat_kesulitan', 'taksonomi_bloom', 'pembahasan', 'status'],
            // Contoh 1 — Soal Umum
            ['umum', '', 'Peraturan perundang-undangan yang mengatur tugas dan fungsi Jabatan Fungsional Transportasi adalah?', 'Permenhub Nomor 4 Tahun 2025', 'Permenhub Nomor 1 Tahun 2020', 'UU No. 22 Tahun 2009', 'PP Nomor 17 Tahun 2020', 'A', 'mudah', 'C1_mengingat', 'Dasar hukum JFT diatur dalam Permenhub Nomor 4 Tahun 2025 tentang tugas dan fungsi Pusbin JFT.', 'draft'],
            // Contoh 2 — Soal Spesifik
            ['spesifik', '2', 'Seorang Penguji Kendaraan Bermotor menemukan kadar CO melebihi ambang batas. Tindakan yang tepat adalah?', 'Lulus uji dengan catatan', 'Tidak lulus uji dan diberikan waktu perbaikan 14 hari', 'Tidak lulus uji dan dilaporkan ke kepolisian', 'Diberikan perpanjangan masa uji', 'B', 'sedang', 'C3_menerapkan', 'Kendaraan yang tidak memenuhi ambang batas emisi dinyatakan tidak lulus uji dan diberi waktu perbaikan.', 'draft'],
            // Contoh 3
            ['umum', '', 'Berikut yang BUKAN termasuk jenjang Jabatan Fungsional kategori Terampil adalah?', 'Pemula', 'Terampil', 'Mahir', 'Ahli Pertama', 'D', 'mudah', 'C2_memahami', 'Ahli Pertama termasuk kategori Ahli, bukan Terampil.', 'aktif'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header biru
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Baris contoh — warna latar lebih terang
        $sheet->getStyle('A2:L4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);

        // Border seluruh tabel
        $sheet->getStyle('A1:L4')->applyFromArray([
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
            'A' => 12, 'B' => 18, 'C' => 50, 'D' => 30, 'E' => 30,
            'F' => 30, 'G' => 30, 'H' => 14, 'I' => 18, 'J' => 20,
            'K' => 40, 'L' => 10,
        ];
    }
}
