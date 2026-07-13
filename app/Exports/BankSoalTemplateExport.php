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
            ['A', 'jenis', 'Jenis soal', 'mansoskul ATAU teknis'],
            ['B', 'soal_kategori_id', 'ID kategori (lihat sheet Daftar Kategori). Wajib diisi jika jenis = teknis, kosongkan jika jenis = mansoskul', '1'],
            ['C', 'matra', 'Matra soal. Wajib diisi jika jenis = mansoskul, kosongkan jika jenis = teknis', 'darat / laut / udara / asdp / perkeretaapian'],
            ['D', 'pertanyaan', 'Teks pertanyaan soal (wajib)', 'Apa yang dimaksud dengan uji emisi kendaraan?'],
            ['E', 'pilihan_a', 'Teks pilihan jawaban A (wajib)', 'Pengujian kadar gas buang'],
            ['F', 'pilihan_b', 'Teks pilihan jawaban B (wajib)', 'Pengujian kelaikan rem'],
            ['G', 'pilihan_c', 'Teks pilihan jawaban C (wajib)', 'Pengujian lampu kendaraan'],
            ['H', 'pilihan_d', 'Teks pilihan jawaban D (wajib)', 'Pengujian kemudi kendaraan'],
            ['I', 'jawaban_benar', 'Kode jawaban yang benar. Wajib diisi jika jenis = teknis, kosongkan jika jenis = mansoskul', 'A ATAU B ATAU C ATAU D'],
            ['J', 'nilai_pilihan_a', 'Nilai skala pilihan A (1-5). Wajib diisi jika jenis = mansoskul, kosongkan jika jenis = teknis', '1 s.d. 5'],
            ['K', 'nilai_pilihan_b', 'Nilai skala pilihan B (1-5). Wajib diisi jika jenis = mansoskul, kosongkan jika jenis = teknis', '1 s.d. 5'],
            ['L', 'nilai_pilihan_c', 'Nilai skala pilihan C (1-5). Wajib diisi jika jenis = mansoskul, kosongkan jika jenis = teknis', '1 s.d. 5'],
            ['M', 'nilai_pilihan_d', 'Nilai skala pilihan D (1-5). Wajib diisi jika jenis = mansoskul, kosongkan jika jenis = teknis', '1 s.d. 5'],
            ['N', 'taksonomi_bloom', 'Taksonomi Bloom', 'C1_mengingat / C2_memahami / C3_menerapkan / C4_menganalisis / C5_mengevaluasi / C6_mencipta'],
            ['O', 'pembahasan', 'Penjelasan jawaban benar (opsional, hanya relevan untuk soal teknis)', 'Uji emisi adalah pengujian terhadap kadar...'],
            ['P', 'status', 'Status soal setelah diimport', 'draft ATAU aktif'],
            [''],
            ['CATATAN PENTING'],
            ['1. Maksimal 500 soal per file'],
            ['2. Format file yang diterima: .xlsx atau .xls'],
            ['3. Jangan ubah nama kolom header di sheet Data Soal'],
            ['4. Soal dengan error akan diskip, soal valid tetap diimport'],
            ['5. Soal Teknis: TIDAK ADA jawaban benar/salah tunggal — setiap pilihan (A-D) diberi nilai skala 1-5 sendiri, bukan is_benar'],
            ['6. Soal Teknis: soal_kategori_id & jawaban_benar wajib diisi. Soal Mansoskul: matra & nilai_pilihan_a/b/c/d wajib diisi'],
            ['7. Lihat sheet "Daftar Kategori" untuk ID kategori yang tersedia (khusus soal Teknis)'],
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
        $sheet->getStyle('A25')->applyFromArray([
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
            ['jenis', 'soal_kategori_id', 'matra', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar', 'nilai_pilihan_a', 'nilai_pilihan_b', 'nilai_pilihan_c', 'nilai_pilihan_d', 'taksonomi_bloom', 'pembahasan', 'status'],
            // Contoh 1 — Soal Mansoskul (skala 1-5, tanpa jawaban benar)
            ['mansoskul', '', 'darat', 'Rekan kerja Anda melakukan kesalahan prosedur yang berpotensi membahayakan keselamatan. Tindakan yang paling tepat adalah?', 'Menegur langsung di depan rekan lain agar jera', 'Melaporkan ke atasan tanpa berbicara dengan rekan tersebut', 'Membicarakan secara empat mata lalu melaporkan sesuai prosedur jika perlu', 'Membiarkan karena bukan tanggung jawab saya', '', '3', '2', '5', '1', 'C5_mengevaluasi', '', 'draft'],
            // Contoh 2 — Soal Teknis (jawaban benar/salah standar)
            ['teknis', '2', '', 'Seorang Penguji Kendaraan Bermotor menemukan kadar CO melebihi ambang batas. Tindakan yang tepat adalah?', 'Lulus uji dengan catatan', 'Tidak lulus uji dan diberikan waktu perbaikan 14 hari', 'Tidak lulus uji dan dilaporkan ke kepolisian', 'Diberikan perpanjangan masa uji', 'B', '', '', '', '', 'C3_menerapkan', 'Kendaraan yang tidak memenuhi ambang batas emisi dinyatakan tidak lulus uji dan diberi waktu perbaikan.', 'draft'],
            // Contoh 3 — Soal Mansoskul lain
            ['mansoskul', '', 'laut', 'Bagaimana sikap Anda saat menerima instruksi mendadak dari atasan di luar jam kerja?', 'Menolak karena sudah di luar jam kerja', 'Menerima dan mengerjakan sebaik mungkin sesuai prioritas', 'Mengerjakan asal-asalan karena terpaksa', 'Mengabaikan instruksi tersebut', '', '1', '5', '2', '1', 'C2_memahami', '', 'aktif'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header biru
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Baris contoh — warna latar lebih terang
        $sheet->getStyle('A2:P4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);

        // Border seluruh tabel
        $sheet->getStyle('A1:P4')->applyFromArray([
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
            'A' => 12, 'B' => 18, 'C' => 16, 'D' => 50, 'E' => 30, 'F' => 30,
            'G' => 30, 'H' => 30, 'I' => 14, 'J' => 16, 'K' => 16, 'L' => 16,
            'M' => 16, 'N' => 20, 'O' => 40, 'P' => 10,
        ];
    }
}
