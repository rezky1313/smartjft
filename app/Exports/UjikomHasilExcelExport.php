<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\UjikomHasil;
use App\Models\UjikomPendaftaranPeserta;

class UjikomHasilExcelExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected int $jadwalId;
    protected string $judulJadwal;

    public function __construct(int $jadwalId, string $judulJadwal = '')
    {
        $this->jadwalId    = $jadwalId;
        $this->judulJadwal = $judulJadwal;
    }

    public function collection()
    {
        // Semua peserta terverifikasi untuk jadwal ini
        $pesertaIds = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) {
            $q->where('ujikom_jadwal_id', $this->jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->pluck('id');

        $hasilMap = UjikomHasil::where('ujikom_jadwal_id', $this->jadwalId)
            ->with(['peserta.pegawai.unitKerja'])
            ->get()
            ->keyBy('peserta_id');

        // Peserta yang belum punya hasil
        $semua = UjikomPendaftaranPeserta::whereIn('id', $pesertaIds)
            ->with(['pegawai.unitKerja', 'jabatanTujuan'])
            ->get();

        $rows = collect();
        foreach ($semua as $i => $p) {
            $hasil = $hasilMap->get($p->id);
            $rows->push([
                'no'                         => $i + 1,
                'nama'                       => $p->pegawai?->nama_lengkap ?? '-',
                'nip'                        => $p->pegawai?->nip ?? '-',
                'unit_kerja'                 => $p->pegawai?->unitKerja?->nama_unit_kerja ?? '-',
                'jabatan_tujuan'             => $p->jabatan_tujuan_nama ?? '-',
                'jenjang_tujuan'             => $p->jenjang_tujuan ?? '-',
                'jenis_ujian'                => $hasil ? ($hasil->jenis_ujian === 'online' ? 'Online' : 'Offline') : '-',
                'tanggal_ujian'              => $hasil?->tanggal_ujian?->format('d/m/Y') ?? '-',
                'nilai_teknis_cat'           => $hasil?->nilai_teknis_cat ?? '-',
                'nilai_teknis_wawancara'     => $hasil?->nilai_teknis_wawancara ?? '-',
                'nilai_teknis_presentasi'    => $hasil?->nilai_teknis_presentasi ?? '-',
                'nilai_teknis'               => $hasil?->nilai_teknis ?? '-',
                'bobot_teknis'               => $hasil?->bobot_teknis ?? '-',
                'nilai_mansoskul_cat'        => $hasil?->nilai_mansoskul_cat ?? '-',
                'nilai_mansoskul_wawancara'  => $hasil?->nilai_mansoskul_wawancara ?? '-',
                'nilai_mansoskul_presentasi' => $hasil?->nilai_mansoskul_presentasi ?? '-',
                'nilai_mansoskul'            => $hasil?->nilai_mansoskul ?? '-',
                'bobot_mansoskul'            => $hasil?->bobot_mansoskul ?? '-',
                'nilai'                      => $hasil?->nilai ?? '-',
                'passing_grade'              => $hasil?->passing_grade ?? '-',
                'status'                     => $hasil ? $hasil->label_status : 'Belum Dinilai',
                'status_kecurangan'          => $hasil ? $hasil->label_kecurangan : '-',
                'catatan'                    => $hasil?->catatan_admin ?? '-',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Peserta',
            'NIP',
            'Unit Kerja',
            'Jabatan Tujuan',
            'Jenjang Tujuan',
            'Jenis Ujian',
            'Tanggal Ujian',
            'Teknis - CAT',
            'Teknis - Wawancara (1-5)',
            'Teknis - Presentasi (1-5)',
            'Teknis - Nilai Gabungan',
            'Teknis - Bobot (%)',
            'Mansoskul - CAT',
            'Mansoskul - Wawancara (1-5)',
            'Mansoskul - Presentasi (1-5)',
            'Mansoskul - Nilai Gabungan',
            'Mansoskul - Bobot (%)',
            'Nilai Akhir',
            'Passing Grade',
            'Status Kelulusan',
            'Status Kecurangan',
            'Catatan Admin',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F3864']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Hasil Ujikom';
    }
}
