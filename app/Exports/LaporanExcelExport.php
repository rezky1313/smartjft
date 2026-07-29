<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanExcelExport implements WithMultipleSheets
{
    protected $tab;
    protected $data;

    public function __construct($tab, $data)
    {
        $this->tab = $tab;
        $this->data = $data;
    }

    /**
     * Return sheets based on tab
     */
    public function sheets(): array
    {
        $sheets = [];

        switch ($this->tab) {
            case 'dashboard':
                $sheets[] = new DashboardSheet($this->data);
                break;

            case 'unit_kerja':
                $sheets[] = new UnitKerjaSheet($this->data);
                break;

            case 'formasi':
                $sheets[] = new FormasiSheet($this->data);
                break;

            case 'pegawai':
                $sheets[] = new PegawaiSheet($this->data);
                break;

            case 'ujikom':
                $sheets[] = new UjikomSheet($this->data);
                break;

            case 'pengangkatan':
                $sheets[] = new PengangkatanSheet($this->data);
                break;

            case 'pendaftaran':
                $sheets[] = new PendaftaranSheet($this->data);
                break;
        }

        return $sheets;
    }
}

/**
 * Sheet for Dashboard
 */
class DashboardSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];

        // Add summary rows
        $rows[] = ['SUMMARY STATISTIK'];
        $rows[] = ['Total Unit Kerja', $this->data['dashboard']['summary']['total_unit_kerja'] ?? 0];
        $rows[] = ['Total Kuota', $this->data['dashboard']['summary']['total_kuota'] ?? 0];
        $rows[] = ['Total Terisi', $this->data['dashboard']['summary']['total_terisi'] ?? 0];
        $rows[] = ['Total Sisa', $this->data['dashboard']['summary']['total_sisa'] ?? 0];
        $rows[] = ['Total Pegawai', $this->data['dashboard']['summary']['total_pegawai'] ?? 0];
        $rows[] = ['Total Di Luar Formasi', $this->data['dashboard']['summary']['total_di_luar_formasi'] ?? 0];
        $rows[] = [];

        // Add jenjang distribution
        $rows[] = ['DISTRIBUSI PEGAWAI PER JENJANG'];
        foreach ($this->data['dashboard']['jenjang_distribution'] as $jenjang => $jumlah) {
            $rows[] = [$jenjang, $jumlah];
        }
        $rows[] = [];

        // Add province summary
        $rows[] = ['RINGKASAN PER PROVINSI'];
        $rows[] = ['No', 'Provinsi', 'Jml Unit Kerja', 'Kuota', 'Terisi', 'Sisa', 'Jml Pegawai'];

        foreach ($this->data['dashboard']['province_summary'] as $i => $row) {
            $rows[] = [
                $i + 1,
                $row['province'],
                $row['jml_unit_kerja'],
                $row['total_kuota'],
                $row['total_terisi'],
                $row['total_sisa'],
                $row['jml_pegawai'],
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
            9 => ['font' => ['bold' => true, 'size' => 12]],
            10 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Dashboard';
    }
}

/**
 * Sheet for Unit Kerja
 */
class UnitKerjaSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];

        foreach ($this->data['unit_kerja'] as $i => $row) {
            $rows[] = [
                $i + 1,
                $row['nama_unit_kerja'],
                $row['jenis_upt'],
                $row['provinsi'],
                $row['kab_kota'],
                $row['jumlah_jabatan_formasi'],
                $row['jumlah_pegawai'],
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Unit Kerja',
            'Jenis UPT',
            'Provinsi',
            'Kab/Kota',
            'Jumlah Jabatan Formasi',
            'Jumlah Pegawai',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Unit Kerja';
    }
}

/**
 * Sheet for Formasi
 */
class FormasiSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];
        $cols = $this->data['formasi']['cols'];

        foreach ($this->data['formasi']['data'] as $i => $row) {
            $rowData = [
                $i + 1,
                $row['unit_kerja'],
                $row['nama_jabatan'],
                $row['tahun'],
            ];

            // Add kuota columns
            foreach ($cols as $c) {
                $rowData[] = $row['kuota'][$c];
            }
            $rowData[] = array_sum($row['kuota']);

            // Add terisi columns
            foreach ($cols as $c) {
                $rowData[] = $row['terisi'][$c];
            }
            $rowData[] = array_sum($row['terisi']);

            // Add sisa columns
            foreach ($cols as $c) {
                $rowData[] = $row['sisa'][$c];
            }
            $rowData[] = array_sum($row['sisa']);

            $rows[] = $rowData;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $headings = ['No', 'Unit Kerja', 'Nama Jabatan', 'Tahun'];
        $cols = $this->data['formasi']['cols'];

        // Add kuota headings
        foreach ($cols as $c) {
            $headings[] = "Kuota - {$c}";
        }
        $headings[] = 'TOTAL Kuota';

        // Add terisi headings
        foreach ($cols as $c) {
            $headings[] = "Terisi - {$c}";
        }
        $headings[] = 'TOTAL Terisi';

        // Add sisa headings
        foreach ($cols as $c) {
            $headings[] = "Sisa - {$c}";
        }
        $headings[] = 'TOTAL Sisa';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Formasi';
    }
}

/**
 * Sheet for Pegawai
 */
class PegawaiSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];

        foreach ($this->data['pegawai'] as $i => $row) {
            // Convert status formasi to text
            $statusFormasi = '-';
            if ($row['status_formasi'] === 'terpenuhi') {
                $statusFormasi = 'Terpenuhi';
            } elseif ($row['status_formasi'] === 'di_luar_formasi') {
                $statusFormasi = 'Di Luar Formasi';
            }

            $rows[] = [
                $i + 1,
                $row['nama'],
                $row['nip'],
                $row['jabatan'],
                $row['jenjang'],
                $row['unit_kerja'],
                $row['provinsi'],
                $row['kab_kota'],
                $row['tmt_jabatan'],
                $statusFormasi,
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Pegawai',
            'NIP',
            'Jabatan',
            'Jenjang',
            'Unit Kerja',
            'Provinsi',
            'Kab/Kota',
            'TMT Jabatan',
            'Status Formasi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Pegawai JFT';
    }
}

/**
 * Sheet for Uji Kompetensi (Tab 5)
 */
class UjikomSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];
        $s = $this->data['ujikom']['summary'];
        $aspek = $this->data['ujikom']['aspek'];
        $kompetensi = $this->data['ujikom']['kompetensi'];

        $rows[] = ['RINGKASAN'];
        $rows[] = ['Total Jadwal', $s['total_jadwal']];
        $rows[] = ['Total Peserta', $s['total_peserta']];
        $rows[] = ['Lulus', $s['lulus']];
        $rows[] = ['Tidak Lulus', $s['tidak_lulus']];
        $rows[] = ['Belum Dinilai', $s['belum_dinilai']];
        $rows[] = ['Tingkat Kelulusan (%)', $s['tingkat_kelulusan']];
        $rows[] = ['Terindikasi Kecurangan', $s['terindikasi_kecurangan']];
        $rows[] = [];

        $rows[] = ['RATA-RATA NILAI PER ASPEK'];
        $rows[] = ['Teknis - CAT', $aspek['teknis_cat']];
        $rows[] = ['Teknis - Wawancara', $aspek['teknis_wawancara']];
        $rows[] = ['Teknis - Presentasi', $aspek['teknis_presentasi']];
        $rows[] = ['Mansoskul - CAT', $aspek['mansoskul_cat']];
        $rows[] = ['Mansoskul - Wawancara', $aspek['mansoskul_wawancara']];
        $rows[] = ['Mansoskul - Presentasi', $aspek['mansoskul_presentasi']];
        $rows[] = [];

        $rows[] = ['RATA-RATA NILAI PER KOMPETENSI'];
        $rows[] = ['Teknis', $kompetensi['teknis']];
        $rows[] = ['Mansoskul', $kompetensi['mansoskul']];
        $rows[] = [];

        $rows[] = ['REKAP PER JADWAL'];
        $rows[] = ['Nama Jadwal', 'Jenjang', 'Jumlah Peserta', 'Lulus', 'Tidak Lulus', 'Belum Dinilai', 'Rata-rata Nilai', 'Kecurangan'];
        foreach ($this->data['ujikom']['per_jadwal'] as $row) {
            $rows[] = [
                $row['jadwal'], $row['jenjang'], $row['jumlah_peserta'],
                $row['lulus'], $row['tidak_lulus'], $row['belum_dinilai'],
                $row['rata_nilai'], $row['kecurangan'],
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Uji Kompetensi';
    }
}

/**
 * Sheet for Pengangkatan JFT (Tab 6)
 */
class PengangkatanSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];
        $s = $this->data['pengangkatan']['summary'];

        $rows[] = ['RINGKASAN'];
        $rows[] = ['Total Permohonan', $s['total_permohonan']];
        $rows[] = ['Total Diangkat', $s['total_diangkat']];
        $rows[] = ['Rata-rata Waktu Proses (hari)', $s['rata_waktu_proses_hari'] ?? '-'];
        $rows[] = [];

        $rows[] = ['REKAP PER UNIT KERJA'];
        $rows[] = ['Unit Kerja', 'Jabatan', 'Jenjang', 'Jumlah Diangkat'];
        foreach ($this->data['pengangkatan']['rekap_unit'] as $unit) {
            if (empty($unit['rincian'])) {
                $rows[] = [$unit['unit_kerja'], '-', '-', 0];
                continue;
            }
            foreach ($unit['rincian'] as $r) {
                $rows[] = [$unit['unit_kerja'], $r['jabatan'], $r['jenjang'], $r['jumlah']];
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Pengangkatan JFT';
    }
}

/**
 * Sheet for Pendaftaran Ujikom (Tab 7)
 */
class PendaftaranSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = [];
        $s = $this->data['pendaftaran']['summary'];

        $rows[] = ['RINGKASAN'];
        $rows[] = ['Total Permohonan', $s['total_permohonan']];
        $rows[] = ['Total Ditolak', $s['total_ditolak']];
        $rows[] = ['Tingkat Penolakan (%)', $s['tingkat_penolakan']];
        $rows[] = [];

        $rows[] = ['BREAKDOWN PER STATUS'];
        foreach ($this->data['pendaftaran']['per_status'] as $row) {
            $rows[] = [$row['label'], $row['jumlah']];
        }
        $rows[] = [];

        $rows[] = ['CATATAN KETERBATASAN DATA'];
        $rows[] = ['Rata-rata waktu verifikasi per tahap (Admin Unit / Pusbin) tidak dapat dihitung karena tabel ujikom_pendaftaran tidak menyimpan timestamp per transisi status, hanya status terakhir.'];
        $rows[] = [];

        $rows[] = ['PERMOHONAN BELUM SELESAI (diurutkan dari paling lama menunggu)'];
        $rows[] = ['Kode', 'Unit Kerja', 'Jadwal', 'Status', 'Menunggu Sejak', 'Jumlah Hari'];
        foreach ($this->data['pendaftaran']['nyangkut'] as $row) {
            $rows[] = [
                $row['kode'], $row['unit_kerja'], $row['jadwal'], $row['status'],
                $row['menunggu_sejak']->format('d-m-Y'), $row['jumlah_hari'],
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Pendaftaran Ujikom';
    }
}
