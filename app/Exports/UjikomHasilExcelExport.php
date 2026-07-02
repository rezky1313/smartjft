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
                'no'              => $i + 1,
                'nama'            => $p->pegawai?->nama_lengkap ?? '-',
                'nip'             => $p->pegawai?->nip ?? '-',
                'unit_kerja'      => $p->pegawai?->unitKerja?->nama_rs ?? '-',
                'jabatan_tujuan'  => $p->jabatan_tujuan_nama ?? '-',
                'jenjang_tujuan'  => $p->jenjang_tujuan ?? '-',
                'jenis_ujian'     => $hasil ? ($hasil->jenis_ujian === 'online' ? 'Online' : 'Offline') : '-',
                'tanggal_ujian'   => $hasil?->tanggal_ujian?->format('d/m/Y') ?? '-',
                'nilai'           => $hasil?->nilai ?? '-',
                'passing_grade'   => $hasil?->passing_grade ?? '-',
                'status'          => $hasil ? $hasil->label_status : 'Belum Dinilai',
                'catatan'         => $hasil?->catatan_admin ?? '-',
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
            'Nilai',
            'Passing Grade',
            'Status Kelulusan',
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
