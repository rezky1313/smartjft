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

class PegawaiTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PegawaiPetunjukSheet(),
            new PegawaiDataSheet(),
        ];
    }
}

// ─── Sheet 1: Petunjuk ────────────────────────────────────────────────────────
class PegawaiPetunjukSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Petunjuk'; }

    public function array(): array
    {
        return [
            ['Template Import Pegawai JFT (SDM) SMART JFT'],
            [''],
            ['PETUNJUK PENGISIAN'],
            [''],
            ['Kolom', 'Nama Header', 'Keterangan', 'Contoh / Nilai Valid'],
            ['A', 'nip', 'NIP pegawai (opsional). Jika NIP diisi dan sudah ada di sistem, data pegawai akan DIUPDATE. Jika kosong, selalu dibuat baris baru', '198501012010011001'],
            ['B', 'nik', 'NIK KTP pegawai (opsional)', '3201012345670001'],
            ['C', 'nama_lengkap', 'Nama lengkap pegawai (wajib)', 'Budi Santoso'],
            ['D', 'jenis_kelamin', 'Jenis kelamin (opsional)', 'L / P'],
            ['E', 'pendidikan_terakhir', 'Pendidikan terakhir (opsional)', 'S1'],
            ['F', 'pangkat_golongan', 'Pangkat/Golongan (opsional)', 'Penata Muda / III/a'],
            ['G', 'status_kepegawaian', 'Status kepegawaian (opsional, default PNS jika kosong)', 'PNS / PPPK / CPNS / Non ASN'],
            ['H', 'aktif', 'Status aktif pegawai (opsional, 1 = aktif, 0 = tidak aktif, default sesuai pilihan "Default Aktif" di form)', '1'],
            ['I', 'nama_formasi', 'Nama formasi/jabatan tujuan (wajib) — harus cocok dengan Nama Jabatan yang sudah ada di menu Formasi. Boleh ditambah jenjang di akhir teks (mis. "Penguji Kendaraan Bermotor Ahli Muda")', 'Penguji Kendaraan Bermotor Ahli Muda'],
            ['J', 'unit_name', 'Nama unit kerja (wajib) — harus cocok dengan nama di menu Unit Kerja. Jika tidak cocok, sistem mencoba fallback dari data formasi', 'Balai Pengujian Kendaraan Bermotor Jakarta'],
            ['K', 'tmt_pengangkatan', 'TMT (Tanggal Mulai Tugas) pengangkatan (opsional). Format tanggal: YYYY-MM-DD', '2024-07-01'],
            [''],
            ['CATATAN PENTING'],
            ['1. Format file yang diterima: .xlsx, .xls, atau .csv'],
            ['2. Jangan ubah nama kolom header di sheet "Data Pegawai"'],
            ['3. Kolom wajib minimal: nama_lengkap, nama_formasi, unit_name'],
            ['4. Jika NIP kosong pada baris, pegawai akan SELALU dibuat sebagai baris baru (bukan update)'],
            ['5. Jika nama_formasi ditulis dengan jenjang di akhir (mis. "... Ahli Muda"), sistem otomatis mencocokkan ke formasi dengan jenjang tersebut'],
            ['6. Format tanggal tmt_pengangkatan harus YYYY-MM-DD, contoh: 2024-07-01'],
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

        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);

        $sheet->getStyle('A5:D5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);

        $sheet->getStyle('A17')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 22, 'C' => 65, 'D' => 45];
    }
}

// ─── Sheet 2: Data Pegawai (Template) ─────────────────────────────────────────
class PegawaiDataSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Data Pegawai'; }

    public function array(): array
    {
        return [
            // Header
            ['nip', 'nik', 'nama_lengkap', 'jenis_kelamin', 'pendidikan_terakhir', 'pangkat_golongan', 'status_kepegawaian', 'aktif', 'nama_formasi', 'unit_name', 'tmt_pengangkatan'],
            // Contoh 1
            ['198501012010011001', '3201012345670001', 'Budi Santoso', 'L', 'S1', 'III/a', 'PNS', 1, 'Penguji Kendaraan Bermotor Ahli Muda', 'Balai Pengujian Kendaraan Bermotor Jakarta', '2024-07-01'],
            // Contoh 2 — tanpa NIP (selalu insert baru)
            ['', '3273012345670002', 'Siti Aminah', 'P', 'S1', 'III/b', 'PPPK', 1, 'Pengawas Keselamatan Pelayaran Ahli Pertama', 'Kantor Kesyahbandaran Utama Tanjung Priok', '2023-01-15'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getStyle('A2:K3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);

        $sheet->getStyle('A1:K3')->applyFromArray([
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
            'A' => 20, 'B' => 20, 'C' => 25, 'D' => 12, 'E' => 16, 'F' => 16,
            'G' => 18, 'H' => 8, 'I' => 35, 'J' => 40, 'K' => 16,
        ];
    }
}
