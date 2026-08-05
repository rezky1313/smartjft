<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PkrWorkingListExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected Collection $sdmList;

    public function __construct(Collection $sdmList)
    {
        $this->sdmList = $sdmList;
    }

    public function collection()
    {
        return $this->sdmList->map(function ($sdm) {
            $p = $sdm->pkr_pangkat;
            return [
                'nama' => $sdm->nama_lengkap,
                'nip' => $sdm->nip,
                'golongan_saat_ini' => $p['golongan_saat_ini'] ?? '-',
                'golongan_tujuan' => $p['golongan_berikutnya'] ?? '-',
                'nama_pangkat_tujuan' => $p['nama_pangkat_berikutnya'] ?? '-',
                'tanggal_prediksi' => $p['tanggal_prediksi'] ? $p['tanggal_prediksi']->format('d/m/Y') : '-',
                'sumber_data' => $p['sumber_perhitungan'] === 'estimasi_nip' ? 'Estimasi dari NIP' : 'Data Tidak Lengkap',
            ];
        });
    }

    public function headings(): array
    {
        return ['Nama', 'NIP', 'Golongan Saat Ini', 'Golongan Tujuan', 'Nama Pangkat Tujuan', 'Tanggal Prediksi', 'Sumber Data'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F3864']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Working List Kenaikan Pangkat';
    }
}
