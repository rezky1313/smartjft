<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExcelExport;
use App\Models\Formasijabatan;
use App\Models\Jenjangjabatan;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Rumahsakit;
use App\Models\Sdmmodels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /**
     * Display the laporan index page with 4 tabs
     */
    public function index(Request $request)
    {
        // Common data for all tabs
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $regencies = collect();
        $unitKerja = collect();

        if ($request->has('province_id') && $request->province_id) {
            $regencies = Regency::where('province_id', $request->province_id)
                ->orderBy('type')->orderBy('name')
                ->get(['id', 'name', 'type', 'province_id']);
        }

        if ($request->has('regency_id') && $request->regency_id) {
            $unitKerja = Rumahsakit::where('regency_id', $request->regency_id)
                ->orderBy('nama_rumahsakit')
                ->get(['no_rs', 'nama_rumahsakit']);
        }

        // Get all unit kerja for filter (if no regency filter)
        if (empty($request->regency_id)) {
            $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')
                ->get(['no_rs', 'nama_rumahsakit']);
        }

        // Data for Tab 1: Dashboard
        $tahun = $request->get('tahun', date('Y'));
        $dashboardData = $this->getDashboardData($tahun, $request->province_id, $request->regency_id);

        // Data for Tab 2: Unit Kerja
        $unitKerjaData = $this->getUnitKerjaData($request->province_id, $request->regency_id);

        // Data for Tab 3: Formasi
        $formasiData = $this->getFormasiData(
            $request->tahun,
            $request->province_id,
            $request->regency_id,
            $request->unit_kerja_id,
            $request->jabatan
        );

        // Data for Tab 4: Pegawai JFT
        $pegawaiData = $this->getPegawaiData(
            $request->tahun,
            $request->unit_kerja_id,
            $request->jabatan,
            $request->jenjang,
            $request->status_formasi
        );

        // Get available years for filter
        $tahuns = Formasijabatan::select('tahun_formasi')
            ->distinct()
            ->orderBy('tahun_formasi')
            ->pluck('tahun_formasi');

        // Get jenjang list
        $jenjangs = Jenjangjabatan::orderBy('kategori')->orderBy('nama_jenjang')
            ->get(['id', 'nama_jenjang']);

        return view('laporan.index', compact(
            'provinces',
            'regencies',
            'unitKerja',
            'tahuns',
            'jenjangs',
            'dashboardData',
            'unitKerjaData',
            'formasiData',
            'pegawaiData',
        ));
    }

    /**
     * Get data for Dashboard tab
     */
    private function getDashboardData($tahun, $provinceId = null, $regencyId = null)
    {
        $cols = ['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'];

        // Query formasi
        $q = Formasijabatan::with([
            'jenjang:id,nama_jenjang',
            'unitkerja:no_rs,nama_rumahsakit,regency_id',
            'unitkerja.regency:id,name,type,province_id',
        ])->withCount(['sdmAktif as terisi']);

        if ($tahun) {
            $q->where('tahun_formasi', $tahun);
        }

        if ($regencyId) {
            $q->whereHas('unitkerja', fn($uq) => $uq->where('regency_id', $regencyId));
        } elseif ($provinceId) {
            $q->whereHas('unitkerja.regency', fn($rq) => $rq->where('province_id', $provinceId));
        }

        $rows = $q->orderBy('unit_kerja_id')->orderBy('nama_formasi')->get();

        // Build summary per province
        $provinceSummary = [];
        $totalStats = [
            'total_unit_kerja' => 0,
            'total_kuota' => 0,
            'total_terisi' => 0,
            'total_sisa' => 0,
            'total_pegawai' => 0,
            'total_di_luar_formasi' => 0,
        ];

        $seenUnits = [];

        foreach ($rows as $f) {
            // Get province name tanpa eager load yang dalam
            $regency = optional($f->unitkerja)->regency;
            $provinceName = $regency ? optional($regency->province)->name : 'Tidak Diketahui';

            if (!isset($provinceSummary[$provinceName])) {
                $provinceSummary[$provinceName] = [
                    'province' => $provinceName,
                    'jml_unit_kerja' => 0,
                    'kuota' => array_fill_keys($cols, 0),
                    'terisi' => array_fill_keys($cols, 0),
                    'sisa' => array_fill_keys($cols, 0),
                    'jml_pegawai' => 0,
                ];
            }

            $unitId = $f->unit_kerja_id;
            if (!in_array($unitId, $seenUnits)) {
                $provinceSummary[$provinceName]['jml_unit_kerja']++;
                $totalStats['total_unit_kerja']++;
                $seenUnits[] = $unitId;
            }

            $lvlName = $this->normLevel(optional($f->jenjang)->nama_jenjang);
            if (!$lvlName) continue;

            $kuota = (int)($f->kuota ?? 0);
            $terisi = (int)($f->terisi ?? 0);

            $provinceSummary[$provinceName]['kuota'][$lvlName] += $kuota;
            $provinceSummary[$provinceName]['terisi'][$lvlName] += $terisi;
            $provinceSummary[$provinceName]['sisa'][$lvlName] = $provinceSummary[$provinceName]['kuota'][$lvlName] - $provinceSummary[$provinceName]['terisi'][$lvlName];
            $provinceSummary[$provinceName]['jml_pegawai'] += $terisi;
        }

        // Calculate totals
        foreach ($provinceSummary as &$data) {
            $data['total_kuota'] = array_sum($data['kuota']);
            $data['total_terisi'] = array_sum($data['terisi']);
            $data['total_sisa'] = array_sum($data['sisa']);

            $totalStats['total_kuota'] += $data['total_kuota'];
            $totalStats['total_terisi'] += $data['total_terisi'];
            $totalStats['total_sisa'] += $data['total_sisa'];
            $totalStats['total_pegawai'] += $data['jml_pegawai'];
        }

        // Get pegawai di luar formasi
        $pegawaiQuery = Sdmmodels::where('aktif', true)
            ->whereHas('formasi.unitkerja.regency', function($q) use ($provinceId, $regencyId) {
                if ($regencyId) {
                    $q->where('id', $regencyId);
                } elseif ($provinceId) {
                    $q->where('province_id', $provinceId);
                }
            });

        $totalStats['total_di_luar_formasi'] = (clone $pegawaiQuery)
            ->where('status_formasi', 'di_luar_formasi')
            ->count();

        // Jenjang distribution
        $jenjangDistribution = [];
        foreach ($cols as $jenjang) {
            $jenjangDistribution[$jenjang] = Sdmmodels::where('aktif', true)
                ->whereHas('formasi.jenjang', fn($q) => $q->where('nama_jenjang', $jenjang))
                ->when($regencyId, fn($q) => $q->whereHas('formasi.unitkerja', fn($uq) => $uq->where('regency_id', $regencyId)))
                ->when($provinceId && !$regencyId, fn($q) => $q->whereHas('formasi.unitkerja.regency', fn($rq) => $rq->where('province_id', $provinceId)))
                ->count();
        }

        return [
            'summary' => $totalStats,
            'province_summary' => array_values($provinceSummary),
            'jenjang_distribution' => $jenjangDistribution,
            'cols' => $cols,
        ];
    }

    /**
     * Get data for Unit Kerja tab
     */
    private function getUnitKerjaData($provinceId = null, $regencyId = null)
    {
        $query = Rumahsakit::with([
            'regency:id,name,type,province_id',
            'regency.province:id,name',
        ])->withCount('formasis');

        if ($regencyId) {
            $query->where('regency_id', $regencyId);
        } elseif ($provinceId) {
            $query->whereHas('regency', fn($q) => $q->where('province_id', $provinceId));
        }

        $units = $query->orderBy('nama_rumahsakit')->get();

        $data = [];
        foreach ($units as $unit) {
            $jumlahPegawai = Sdmmodels::where('aktif', true)
                ->where(function($q) use ($unit) {
                    $q->where('unit_kerja_id', $unit->no_rs)
                        ->orWhereHas('formasi', fn($f) => $f->where('unit_kerja_id', $unit->no_rs));
                })
                ->count();

            $data[] = [
                'nama_unit_kerja' => $unit->nama_rumahsakit,
                'jenis_upt' => $unit->jenis_upt ?? '-',
                'provinsi' => optional($unit->regency)->province->name ?? '-',
                'kab_kota' => optional($unit->regency)->type . ' ' . optional($unit->regency)->name,
                'jumlah_jabatan_formasi' => $unit->formasis_count ?? 0,
                'jumlah_pegawai' => $jumlahPegawai,
            ];
        }

        return $data;
    }

    /**
     * Get data for Formasi tab
     */
    private function getFormasiData($tahun = null, $provinceId = null, $regencyId = null, $unitKerjaId = null, $jabatan = null)
    {
        $cols = ['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'];

        $q = Formasijabatan::with([
            'jenjang:id,nama_jenjang',
            'unitkerja:no_rs,nama_rumahsakit,regency_id',
            'unitkerja.regency:id,name,type,province_id',
        ])->withCount(['sdmAktif as terisi']);

        if ($tahun) {
            $q->where('tahun_formasi', $tahun);
        }

        if ($unitKerjaId) {
            $q->where('unit_kerja_id', $unitKerjaId);
        }

        if ($jabatan) {
            $q->where('nama_formasi', $jabatan);
        }

        if ($regencyId) {
            $q->whereHas('unitkerja', fn($uq) => $uq->where('regency_id', $regencyId));
        } elseif ($provinceId) {
            $q->whereHas('unitkerja.regency', fn($rq) => $rq->where('province_id', $provinceId));
        }

        $rows = $q->orderBy('unit_kerja_id')->orderBy('nama_formasi')->get();

        // Build table data grouped by unit + jabatan
        $table = [];
        foreach ($rows as $f) {
            $unitName = optional($f->unitkerja)->nama_rumahsakit ?? ('Unit #'.$f->unit_kerja_id);
            $jabatanName = $f->nama_formasi ?? '-';

            $key = md5($unitName.'|'.$jabatanName);

            if (!isset($table[$key])) {
                $table[$key] = [
                    'unit_kerja' => $unitName,
                    'nama_jabatan' => $jabatanName,
                    'tahun' => $f->tahun_formasi,
                    'kuota' => array_fill_keys($cols, 0),
                    'terisi' => array_fill_keys($cols, 0),
                    'sisa' => array_fill_keys($cols, 0),
                ];
            }

            $lvlName = $this->normLevel(optional($f->jenjang)->nama_jenjang);
            if (!$lvlName) continue;

            $kuota = (int)($f->kuota ?? 0);
            $terisi = (int)($f->terisi ?? 0);

            $table[$key]['kuota'][$lvlName] += $kuota;
            $table[$key]['terisi'][$lvlName] += $terisi;
            $table[$key]['sisa'][$lvlName] = $table[$key]['kuota'][$lvlName] - $table[$key]['terisi'][$lvlName];
        }

        return [
            'cols' => $cols,
            'data' => array_values($table),
        ];
    }

    /**
     * Get data for Pegawai JFT tab
     */
    private function getPegawaiData($tahun = null, $unitKerjaId = null, $jabatan = null, $jenjangId = null, $statusFormasi = null)
    {
        // Optimasi: Kurangi eager load untuk menghemat memory
        $query = Sdmmodels::with([
            'formasi:id,nama_formasi,unit_kerja_id,tahun_formasi',
            'formasi.jenjang:id,nama_jenjang',
            'formasi.unitkerja:no_rs,nama_rumahsakit,regency_id',
            'formasi.unitkerja.regency:id,name,type,province_id',
            'unitKerja:no_rs,nama_rumahsakit,regency_id',
            'unitKerja.regency:id,name,type,province_id',
        ])->where('aktif', true);

        if ($tahun) {
            $query->whereHas('formasi', fn($q) => $q->where('tahun_formasi', $tahun));
        }

        if ($unitKerjaId) {
            $query->where(function($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId)
                    ->orWhereHas('formasi', fn($f) => $f->where('unit_kerja_id', $unitKerjaId));
            });
        }

        if ($jabatan) {
            $query->whereHas('formasi', fn($q) => $q->where('nama_formasi', $jabatan));
        }

        if ($jenjangId) {
            $query->whereHas('formasi', fn($q) => $q->where('jenjang_id', $jenjangId));
        }

        if ($statusFormasi && in_array($statusFormasi, ['terpenuhi', 'di_luar_formasi'])) {
            $query->where('status_formasi', $statusFormasi);
        }

        $pegawai = $query->orderBy('nama_lengkap')->get();

        $data = [];
        foreach ($pegawai as $p) {
            $formasi = $p->formasi;
            $unitKerja = $formasi?->unitkerja ?? $p->unitKerja;

            // Get province lebih efisien tanpa eager load berlebih
            if ($unitKerja && $unitKerja->regency) {
                $regency = $unitKerja->regency;
                $province = optional($regency->province)->name;
            } else {
                $regency = $p->unitKerja?->regency;
                $province = optional($regency?->province)->name;
            }

            $data[] = [
                'nama' => $p->nama_lengkap,
                'nip' => $p->nip ?? '-',
                'jabatan' => $formasi?->nama_formasi ?? '-',
                'jenjang' => $formasi?->jenjang?->nama_jenjang ?? '-',
                'unit_kerja' => $unitKerja?->nama_rumahsakit ?? '-',
                'provinsi' => $province?->name ?? '-',
                'kab_kota' => $regency ? $regency->type . ' ' . $regency->name : '-',
                'tmt_jabatan' => $p->tmt_pengangkatan?->format('d-m-Y') ?? '-',
                'status_formasi' => $p->status_formasi ?? '-',
            ];
        }

        return $data;
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request, $tab)
    {
        // Increase memory limit untuk PDF generation
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $data = $this->getPdfData($tab, $request);

        $pdf = PDF::loadView("laporan.pdf.{$tab}", $data);
        $pdf->setPaper('a4', 'landscape');

        $filename = "laporan-{$tab}-" . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get data for PDF export
     */
    private function getPdfData($tab, $request)
    {
        $data = [
            'title' => $this->getTabTitle($tab),
            'tanggal_cetak' => date('d/m/Y H:i'),
            'filter_params' => $this->getFilterParams($request),
            'kop_surat_path' => public_path('images/kop_surat.png'),
        ];

        switch ($tab) {
            case 'dashboard':
                $data['dashboard'] = $this->getDashboardData(
                    $request->get('tahun'),
                    $request->get('province_id'),
                    $request->get('regency_id')
                );
                break;

            case 'unit_kerja':
                $data['unit_kerja'] = $this->getUnitKerjaData(
                    $request->get('province_id'),
                    $request->get('regency_id')
                );
                break;

            case 'formasi':
                $data['formasi'] = $this->getFormasiData(
                    $request->get('tahun'),
                    $request->get('province_id'),
                    $request->get('regency_id'),
                    $request->get('unit_kerja_id'),
                    $request->get('jabatan')
                );
                break;

            case 'pegawai':
                $data['pegawai'] = $this->getPegawaiData(
                    $request->get('tahun'),
                    $request->get('unit_kerja_id'),
                    $request->get('jabatan'),
                    $request->get('jenjang'),
                    $request->get('status_formasi')
                );
                break;
        }

        return $data;
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request, $tab)
    {
        $data = $this->getPdfData($tab, $request);

        $filename = "laporan-{$tab}-" . date('Y-m-d') . '.xlsx';

        return Excel::download(new LaporanExcelExport($tab, $data), $filename);
    }

    /**
     * Helper: Normalize jenjang name
     */
    private function normLevel($name)
    {
        if (!$name) return null;

        $map = [
            'Pemula' => 'Pemula',
            'Terampil' => 'Terampil',
            'Mahir' => 'Mahir',
            'Penyelia' => 'Penyelia',
            'Ahli Pertama' => 'Ahli Pertama',
            'Ahli Muda' => 'Ahli Muda',
            'Ahli Madya' => 'Ahli Madya',
            'Ahli Utama' => 'Ahli Utama',
        ];

        return $map[$name] ?? null;
    }

    /**
     * Helper: Get tab title
     */
    private function getTabTitle($tab)
    {
        $titles = [
            'dashboard' => 'Laporan Dashboard',
            'unit_kerja' => 'Laporan Unit Kerja',
            'formasi' => 'Laporan Formasi',
            'pegawai' => 'Laporan Pegawai JFT',
        ];

        return $titles[$tab] ?? 'Laporan';
    }

    /**
     * Helper: Get filter params for display
     */
    private function getFilterParams($request)
    {
        $params = [];

        if ($request->get('tahun')) {
            $params['Tahun'] = $request->get('tahun');
        }

        if ($request->get('province_id')) {
            $province = Province::find($request->get('province_id'));
            $params['Provinsi'] = $province?->name ?? $request->get('province_id');
        }

        if ($request->get('regency_id')) {
            $regency = Regency::find($request->get('regency_id'));
            $params['Kab/Kota'] = ($regency?->type ?? '') . ' ' . ($regency?->name ?? '');
        }

        if ($request->get('unit_kerja_id')) {
            $unit = Rumahsakit::find($request->get('unit_kerja_id'));
            $params['Unit Kerja'] = $unit?->nama_rumahsakit ?? $request->get('unit_kerja_id');
        }

        if ($request->get('jabatan')) {
            $params['Jabatan'] = $request->get('jabatan');
        }

        if ($request->get('jenjang')) {
            $jenjang = Jenjangjabatan::find($request->get('jenjang'));
            $params['Jenjang'] = $jenjang?->nama_jenjang ?? $request->get('jenjang');
        }

        if ($request->get('status_formasi')) {
            $params['Status Formasi'] = $request->get('status_formasi') === 'terpenuhi' ? 'Terpenuhi' : 'Di Luar Formasi';
        }

        return $params;
    }
}
