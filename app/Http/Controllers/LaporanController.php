<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExcelExport;
use App\Models\Formasijabatan;
use App\Models\Jenjangjabatan;
use App\Models\Province;
use App\Models\Regency;
use App\Models\UnitKerja;
use App\Models\Sdmmodels;
use App\Models\UjikomHasil;
use App\Models\UjikomJadwal;
use App\Models\UjikomPendaftaran;
use App\Models\PengangkatanPermohonan;
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
            $unitKerja = UnitKerja::where('regency_id', $request->regency_id)
                ->orderBy('nama_unit_kerja')
                ->get(['id', 'nama_unit_kerja']);
        }

        // Get all unit kerja for filter (if no regency filter)
        if (empty($request->regency_id)) {
            $unitKerja = UnitKerja::orderBy('nama_unit_kerja')
                ->get(['id', 'nama_unit_kerja']);
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

        // Data for Tab 5: Uji Kompetensi
        $ujikomData = $this->getUjikomData(
            $request->tahun_ujikom,
            $request->jadwal_id,
            $request->jenjang_ujikom,
            $request->unit_kerja_id
        );

        // Data for Tab 6: Pengangkatan JFT
        $pengangkatanData = $this->getPengangkatanData(
            $request->tahun_pengangkatan,
            $request->unit_kerja_id,
            $request->jabatan
        );

        // Data for Tab 7: Pendaftaran Ujikom
        $pendaftaranData = $this->getPendaftaranData(
            $request->tahun_pendaftaran,
            $request->unit_kerja_id
        );

        // Filter helpers for Tab 5-7
        $jadwalList = UjikomJadwal::orderByDesc('tanggal_mulai')->get(['id', 'judul']);
        $jenjangUjikomOptions = $this->jenjangTujuanOptions();
        $tahunsUjikom = UjikomJadwal::whereNotNull('tanggal_mulai')
            ->selectRaw('YEAR(tanggal_mulai) as th')->distinct()->orderByDesc('th')->pluck('th');
        $tahunsPengangkatan = PengangkatanPermohonan::whereNotNull('tanggal_permohonan')
            ->selectRaw('YEAR(tanggal_permohonan) as th')->distinct()->orderByDesc('th')->pluck('th');
        $tahunsPendaftaran = UjikomPendaftaran::selectRaw('YEAR(created_at) as th')
            ->distinct()->orderByDesc('th')->pluck('th');

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
            'ujikomData',
            'pengangkatanData',
            'pendaftaranData',
            'jadwalList',
            'jenjangUjikomOptions',
            'tahunsUjikom',
            'tahunsPengangkatan',
            'tahunsPendaftaran',
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
            'unitkerja:id,nama_unit_kerja,regency_id',
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
        $query = UnitKerja::with([
            'regency:id,name,type,province_id',
            'regency.province:id,name',
        ])->withCount('formasis');

        if ($regencyId) {
            $query->where('regency_id', $regencyId);
        } elseif ($provinceId) {
            $query->whereHas('regency', fn($q) => $q->where('province_id', $provinceId));
        }

        $units = $query->orderBy('nama_unit_kerja')->get();

        $data = [];
        foreach ($units as $unit) {
            $jumlahPegawai = Sdmmodels::where('aktif', true)
                ->where(function($q) use ($unit) {
                    $q->where('unit_kerja_id', $unit->id)
                        ->orWhereHas('formasi', fn($f) => $f->where('unit_kerja_id', $unit->id));
                })
                ->count();

            $data[] = [
                'nama_unit_kerja' => $unit->nama_unit_kerja,
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
            'unitkerja:id,nama_unit_kerja,regency_id',
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
            $unitName = optional($f->unitkerja)->nama_unit_kerja ?? ('Unit #'.$f->unit_kerja_id);
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
            'formasi.unitkerja:id,nama_unit_kerja,regency_id',
            'formasi.unitkerja.regency:id,name,type,province_id',
            'unitKerja:id,nama_unit_kerja,regency_id',
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
                'unit_kerja' => $unitKerja?->nama_unit_kerja ?? '-',
                'provinsi' => $province?->name ?? '-',
                'kab_kota' => $regency ? $regency->type . ' ' . $regency->name : '-',
                'tmt_jabatan' => $p->tmt_pengangkatan?->format('d-m-Y') ?? '-',
                'status_formasi' => $p->status_formasi ?? '-',
            ];
        }

        return $data;
    }

    /**
     * Get data for Uji Kompetensi tab (Tab 5)
     */
    private function getUjikomData($tahun = null, $jadwalId = null, $jenjang = null, $unitKerjaId = null)
    {
        $q = UjikomHasil::with([
            'jadwal:id,judul,jenis_ujian,jenjang_tujuan,tanggal_mulai,tanggal_selesai',
            'peserta:id,ujikom_pendaftaran_id,pegawai_id',
            'peserta.pendaftaran:id,unit_kerja_id',
            'peserta.pendaftaran.unitKerja:id,nama_unit_kerja',
            'peserta.pegawai:id,nama_lengkap',
        ]);

        if ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId);
        }

        if ($tahun) {
            $q->whereHas('jadwal', fn($jq) => $jq->whereYear('tanggal_mulai', $tahun));
        }

        if ($jenjang) {
            $q->whereHas('jadwal', fn($jq) => $jq->where('jenjang_tujuan', $jenjang));
        }

        if ($unitKerjaId) {
            $q->whereHas('peserta.pendaftaran', fn($pq) => $pq->where('unit_kerja_id', $unitKerjaId));
        }

        $rows = $q->get();

        $lulus = $rows->where('status_kelulusan', 'lulus')->count();
        $tidakLulus = $rows->where('status_kelulusan', 'tidak_lulus')->count();
        $belumDinilai = $rows->where('status_kelulusan', 'belum_dinilai')->count();
        $sudahDinilai = $lulus + $tidakLulus;
        $tingkatKelulusan = $sudahDinilai > 0 ? round(($lulus / $sudahDinilai) * 100, 1) : 0;

        // Rekap per jadwal
        $perJadwal = [];
        foreach ($rows->groupBy('ujikom_jadwal_id') as $jid => $group) {
            $jadwal = $group->first()->jadwal;
            $perJadwal[] = [
                'jadwal' => $jadwal?->judul ?? "Jadwal #{$jid}",
                'jenjang' => $jadwal?->label_jenjang_tujuan ?? '-',
                'jumlah_peserta' => $group->count(),
                'lulus' => $group->where('status_kelulusan', 'lulus')->count(),
                'tidak_lulus' => $group->where('status_kelulusan', 'tidak_lulus')->count(),
                'belum_dinilai' => $group->where('status_kelulusan', 'belum_dinilai')->count(),
                'rata_nilai' => round($group->avg('nilai') ?? 0, 2),
                'kecurangan' => $group->where('status_kecurangan', 'terindikasi')->count(),
            ];
        }

        // Tren kelulusan per periode (tahun jadwal)
        $tren = [];
        foreach ($rows->groupBy(fn($r) => optional($r->jadwal?->tanggal_mulai)->format('Y') ?? '-') as $periode => $group) {
            if ($periode === '-') continue;
            $l = $group->where('status_kelulusan', 'lulus')->count();
            $tl = $group->where('status_kelulusan', 'tidak_lulus')->count();
            $sudah = $l + $tl;
            $tren[$periode] = $sudah > 0 ? round(($l / $sudah) * 100, 1) : 0;
        }
        ksort($tren);

        return [
            'summary' => [
                'total_jadwal' => $rows->pluck('ujikom_jadwal_id')->unique()->count(),
                'total_peserta' => $rows->count(),
                'lulus' => $lulus,
                'tidak_lulus' => $tidakLulus,
                'belum_dinilai' => $belumDinilai,
                'tingkat_kelulusan' => $tingkatKelulusan,
                'terindikasi_kecurangan' => $rows->where('status_kecurangan', 'terindikasi')->count(),
            ],
            'per_jadwal' => $perJadwal,
            'tren' => $tren,
            'aspek' => [
                'teknis_cat' => round($rows->avg('nilai_teknis_cat') ?? 0, 2),
                'teknis_wawancara' => round($rows->avg('nilai_teknis_wawancara') ?? 0, 2),
                'teknis_presentasi' => round($rows->avg('nilai_teknis_presentasi') ?? 0, 2),
                'mansoskul_cat' => round($rows->avg('nilai_mansoskul_cat') ?? 0, 2),
                'mansoskul_wawancara' => round($rows->avg('nilai_mansoskul_wawancara') ?? 0, 2),
                'mansoskul_presentasi' => round($rows->avg('nilai_mansoskul_presentasi') ?? 0, 2),
            ],
            'kompetensi' => [
                'teknis' => round($rows->avg('nilai_teknis') ?? 0, 2),
                'mansoskul' => round($rows->avg('nilai_mansoskul') ?? 0, 2),
            ],
        ];
    }

    /**
     * Get data for Pengangkatan JFT tab (Tab 6)
     *
     * CATATAN: Filter/breakdown "Jalur Pengangkatan" TIDAK dibuat -- kolom
     * jalur sudah dihapus total dari skema sejak penyederhanaan alur v1.14.0
     * (lihat CHANGELOG v1.14.0). Disepakati dengan user untuk di-skip tanpa
     * migration baru, lihat CHANGELOG v1.21.0.
     */
    private function getPengangkatanData($tahun = null, $unitKerjaId = null, $jabatan = null)
    {
        $q = PengangkatanPermohonan::with([
            'unitKerja:id,nama_unit_kerja',
            'kandidat:id,pengangkatan_permohonan_id,jabatan_tujuan_id,jenjang_tujuan,status_kandidat',
            'kandidat.jabatanTujuan:id,nama_formasi',
        ]);

        if ($tahun) {
            $q->whereYear('tanggal_permohonan', $tahun);
        }

        if ($unitKerjaId) {
            $q->where('unit_kerja_id', $unitKerjaId);
        }

        if ($jabatan) {
            $q->whereHas('kandidat.jabatanTujuan', fn($jq) => $jq->where('nama_formasi', 'like', "%{$jabatan}%"));
        }

        $permohonan = $q->get();

        $totalDiangkat = 0;
        $rekapUnit = [];
        $waktuProses = [];
        $tren = [];

        foreach ($permohonan as $p) {
            $kandidatDiangkat = $p->status === 'selesai'
                ? $p->kandidat->where('status_kandidat', 'direkomendasikan')
                : collect();

            $totalDiangkat += $kandidatDiangkat->count();

            $unitName = $p->unitKerja?->nama_unit_kerja ?? 'Tidak Diketahui';
            if (!isset($rekapUnit[$unitName])) {
                $rekapUnit[$unitName] = ['unit_kerja' => $unitName, 'jumlah_diangkat' => 0, 'rincian' => []];
            }

            foreach ($kandidatDiangkat as $k) {
                $rekapUnit[$unitName]['jumlah_diangkat']++;
                $namaJabatan = $k->jabatanTujuan?->nama_formasi ?? '-';
                $jenjangTujuan = $k->jenjang_tujuan ?? '-';
                $key = $namaJabatan . '|' . $jenjangTujuan;

                if (!isset($rekapUnit[$unitName]['rincian'][$key])) {
                    $rekapUnit[$unitName]['rincian'][$key] = [
                        'jabatan' => $namaJabatan,
                        'jenjang' => $jenjangTujuan,
                        'jumlah' => 0,
                    ];
                }
                $rekapUnit[$unitName]['rincian'][$key]['jumlah']++;
            }

            if ($p->status === 'selesai' && $p->tanggal_permohonan && $p->tanggal_disetujui) {
                $waktuProses[] = $p->tanggal_permohonan->diffInDays($p->tanggal_disetujui);

                $tahunKe = $p->tanggal_disetujui->format('Y');
                $tren[$tahunKe] = ($tren[$tahunKe] ?? 0) + $kandidatDiangkat->count();
            }
        }

        foreach ($rekapUnit as &$u) {
            $u['rincian'] = array_values($u['rincian']);
        }
        unset($u);

        ksort($tren);

        return [
            'summary' => [
                'total_permohonan' => $permohonan->count(),
                'total_diangkat' => $totalDiangkat,
                'rata_waktu_proses_hari' => count($waktuProses) > 0
                    ? round(array_sum($waktuProses) / count($waktuProses), 1)
                    : null,
            ],
            'rekap_unit' => array_values($rekapUnit),
            'tren' => $tren,
        ];
    }

    /**
     * Get data for Pendaftaran Ujikom tab (Tab 7)
     *
     * KETERBATASAN DATA: tabel ujikom_pendaftaran hanya menyimpan status
     * TERAKHIR + created_at/updated_at, tanpa timestamp per transisi status.
     * Karena itu "rata-rata waktu verifikasi per tahap (Admin Unit vs Pusbin)"
     * tidak bisa dihitung akurat -- lihat flag 'keterbatasan_waktu_verifikasi'
     * yang dipakai view untuk menampilkan catatan ini secara eksplisit.
     */
    private function getPendaftaranData($tahun = null, $unitKerjaId = null)
    {
        $q = UjikomPendaftaran::with(['unitKerja:id,nama_unit_kerja', 'jadwal:id,judul']);

        if ($tahun) {
            $q->whereYear('created_at', $tahun);
        }

        if ($unitKerjaId) {
            $q->where('unit_kerja_id', $unitKerjaId);
        }

        $rows = $q->orderBy('created_at')->get();
        $total = $rows->count();

        $statusList = [
            'draft', 'diajukan_admin_unit', 'diverifikasi_admin_unit',
            'diajukan_pusbin', 'diverifikasi_pusbin',
            'ditolak_admin_unit', 'ditolak_pusbin', 'selesai',
        ];

        $perStatus = [];
        foreach ($statusList as $s) {
            $perStatus[$s] = [
                'label' => (new UjikomPendaftaran(['status' => $s]))->label_status,
                'jumlah' => $rows->where('status', $s)->count(),
            ];
        }

        $statusPending = ['draft', 'diajukan_admin_unit', 'diverifikasi_admin_unit', 'diajukan_pusbin', 'diverifikasi_pusbin'];
        $statusDitolak = ['ditolak_admin_unit', 'ditolak_pusbin'];

        $nyangkut = $rows->whereIn('status', $statusPending)
            ->sortBy('created_at')
            ->map(fn($r) => [
                'kode' => $r->kode_pendaftaran,
                'unit_kerja' => $r->unitKerja?->nama_unit_kerja ?? '-',
                'jadwal' => $r->jadwal?->judul ?? '-',
                'status' => $r->label_status,
                'badge' => $r->badge_status,
                'menunggu_sejak' => $r->created_at,
                'jumlah_hari' => (int) $r->created_at->diffInDays(now()),
            ])->values();

        $totalDitolak = $rows->whereIn('status', $statusDitolak)->count();

        $catatanPenolakan = $rows->whereIn('status', $statusDitolak)
            ->filter(fn($r) => !empty($r->catatan_admin_unit) || !empty($r->catatan_pusbin))
            ->map(fn($r) => [
                'kode' => $r->kode_pendaftaran,
                'unit_kerja' => $r->unitKerja?->nama_unit_kerja ?? '-',
                'status' => $r->label_status,
                'catatan' => $r->catatan_pusbin ?: $r->catatan_admin_unit,
            ])->values();

        return [
            'summary' => [
                'total_permohonan' => $total,
                'total_ditolak' => $totalDitolak,
                'tingkat_penolakan' => $total > 0 ? round(($totalDitolak / $total) * 100, 1) : 0,
            ],
            'per_status' => $perStatus,
            'nyangkut' => $nyangkut,
            'catatan_penolakan' => $catatanPenolakan,
            'keterbatasan_waktu_verifikasi' => true,
        ];
    }

    /**
     * Helper: Opsi jenjang tujuan (enum string) untuk filter Tab 5
     */
    private function jenjangTujuanOptions(): array
    {
        return [
            'pemula' => 'Pemula',
            'terampil' => 'Terampil',
            'mahir' => 'Mahir',
            'penyelia' => 'Penyelia',
            'ahli_pertama' => 'Ahli Pertama',
            'ahli_muda' => 'Ahli Muda',
            'ahli_madya' => 'Ahli Madya',
            'ahli_utama' => 'Ahli Utama',
        ];
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

            case 'ujikom':
                $data['ujikom'] = $this->getUjikomData(
                    $request->get('tahun_ujikom'),
                    $request->get('jadwal_id'),
                    $request->get('jenjang_ujikom'),
                    $request->get('unit_kerja_id')
                );
                break;

            case 'pengangkatan':
                $data['pengangkatan'] = $this->getPengangkatanData(
                    $request->get('tahun_pengangkatan'),
                    $request->get('unit_kerja_id'),
                    $request->get('jabatan')
                );
                break;

            case 'pendaftaran':
                $data['pendaftaran'] = $this->getPendaftaranData(
                    $request->get('tahun_pendaftaran'),
                    $request->get('unit_kerja_id')
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
            'ujikom' => 'Laporan Uji Kompetensi',
            'pengangkatan' => 'Laporan Pengangkatan JFT',
            'pendaftaran' => 'Laporan Pendaftaran Ujikom',
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
            $unit = UnitKerja::find($request->get('unit_kerja_id'));
            $params['Unit Kerja'] = $unit?->nama_unit_kerja ?? $request->get('unit_kerja_id');
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

        if ($request->get('jadwal_id')) {
            $jadwal = UjikomJadwal::find($request->get('jadwal_id'));
            $params['Jadwal Ujikom'] = $jadwal?->judul ?? $request->get('jadwal_id');
        }

        if ($request->get('jenjang_ujikom')) {
            $params['Jenjang'] = $this->jenjangTujuanOptions()[$request->get('jenjang_ujikom')] ?? $request->get('jenjang_ujikom');
        }

        if ($request->get('tahun_ujikom')) {
            $params['Tahun'] = $request->get('tahun_ujikom');
        }

        if ($request->get('tahun_pengangkatan')) {
            $params['Tahun'] = $request->get('tahun_pengangkatan');
        }

        if ($request->get('tahun_pendaftaran')) {
            $params['Tahun'] = $request->get('tahun_pendaftaran');
        }

        return $params;
    }
}
