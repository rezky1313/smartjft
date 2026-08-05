<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Sdmmodels;
use App\Models\PkrAngkaKreditRiwayat;
use App\Models\PkrReferensiKoefisien;
use App\Models\PkrReferensiPredikat;
use App\Models\PkrAmbangBatasJenjang;
use App\Models\PkrReferensiPangkat;
use App\Models\UjikomHasil;
use App\Models\Formasijabatan;

class PkrController extends Controller
{
    /** Jenjang short-code kategori keterampilan (dipakai untuk menentukan kategori di ambangBatasNaikJenjang). */
    private const JENJANG_KETERAMPILAN = ['pemula', 'terampil', 'mahir', 'penyelia'];

    /**
     * Halaman command center PKR per pegawai: ringkasan AK kumulatif,
     * ambang batas jenjang berikutnya, riwayat, dan form input baru.
     */
    public function show($sdmId)
    {
        $sdm = Sdmmodels::with(['formasi.jenjang', 'unitKerja'])->findOrFail($sdmId);

        $riwayat = PkrAngkaKreditRiwayat::where('sdm_id', $sdmId)
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->get();

        $akKumulatif = $this->hitungAkKumulatif($sdmId);
        $ambangBatas = $sdm->jenjang_kode ? $this->ambangBatasNaikJenjang($sdm->jenjang_kode) : null;

        $daftarPredikat = PkrReferensiPredikat::orderByDesc('persentase')->get();
        $koefisienJenjang = $sdm->jenjang_kode
            ? PkrReferensiKoefisien::where('jenjang', $sdm->jenjang_kode)->value('koefisien_tahunan')
            : null;

        return view('pkr.show', compact(
            'sdm', 'riwayat', 'akKumulatif', 'ambangBatas', 'daftarPredikat', 'koefisienJenjang'
        ));
    }

    public function hitungAkKumulatif($sdmId): float
    {
        return (float) PkrAngkaKreditRiwayat::where('sdm_id', $sdmId)->sum('angka_kredit_diperoleh');
    }

    public function ambangBatasNaikJenjang(string $jenjangSaatIni): ?array
    {
        $kategori = in_array($jenjangSaatIni, self::JENJANG_KETERAMPILAN) ? 'keterampilan' : 'keahlian';

        return PkrAmbangBatasJenjang::where('kategori', $kategori)
            ->where('dari_jenjang', $jenjangSaatIni)
            ->first()?->toArray();
    }

    public function storeAngkaKredit(Request $request, $sdmId)
    {
        $request->validate([
            'periode_bulan' => 'required|string|max:255',
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'jumlah_bulan' => 'required|integer|min:1|max:12',
            'predikat_kinerja' => 'required|in:sangat_baik,baik,cukup,kurang,sangat_kurang',
            'catatan' => 'nullable|string',
        ]);

        $sdm = Sdmmodels::with('formasi.jenjang')->findOrFail($sdmId);

        $jenjangKode = $sdm->jenjang_kode;
        if (!$jenjangKode) {
            return back()->with('error', 'Jenjang pegawai ini tidak dapat ditentukan (formasi/jenjang belum lengkap).');
        }

        $koefisien = PkrReferensiKoefisien::where('jenjang', $jenjangKode)->value('koefisien_tahunan');
        $persentase = PkrReferensiPredikat::where('predikat', $request->predikat_kinerja)->value('persentase');

        if (!$koefisien || !$persentase) {
            return back()->with('error', 'Data referensi koefisien/predikat tidak ditemukan.');
        }

        $akDihitung = PkrAngkaKreditRiwayat::hitungAK((int) $request->jumlah_bulan, (float) $persentase, (float) $koefisien);

        PkrAngkaKreditRiwayat::create([
            'sdm_id' => $sdmId,
            'tahun' => $request->tahun ?? date('Y'),
            'periode_bulan' => $request->periode_bulan,
            'jumlah_bulan' => $request->jumlah_bulan,
            'predikat_kinerja' => $request->predikat_kinerja,
            'persentase_predikat' => $persentase,
            'koefisien_tahunan' => $koefisien,
            'angka_kredit_diperoleh' => $akDihitung,
            'jenjang_saat_itu' => $sdm->jenjang_nama ?? $jenjangKode,
            'catatan' => $request->catatan,
            'dinilai_oleh' => auth()->id(),
        ]);

        return back()->with('success', "Angka Kredit sebesar {$akDihitung} berhasil dicatat.");
    }

    /** Peta short-code -> akhiran nama jenjang resmi (kebalikan dari Sdmmodels::getJenjangKodeAttribute). */
    private const PETA_KODE_KE_SUFFIX = [
        'ahli_pertama' => 'Ahli Pertama',
        'ahli_muda' => 'Ahli Muda',
        'ahli_madya' => 'Ahli Madya',
        'ahli_utama' => 'Ahli Utama',
        'penyelia' => 'Penyelia',
        'terampil' => 'Terampil',
        'mahir' => 'Mahir',
        'pemula' => 'Pemula',
    ];

    /**
     * Prediksi kenaikan pangkat/golongan berkala (siklus reguler 4 tahun).
     *
     * KEPUTUSAN (dikonfirmasi user, 4 Agustus 2026): SELALU pakai estimasi dari NIP
     * sebagai dasar tanggal -- tmt_pengangkatan TIDAK dipakai sebagai proxy TMT pangkat
     * karena maknanya beda (TMT masuk jenjang JFT, bukan TMT kenaikan pangkat/golongan)
     * dan kosong di >99% data produksi. Ini hanya perkiraan kasar siklus reguler, TIDAK
     * memvalidasi syarat lain (predikat kinerja, dll).
     */
    public function prediksiKenaikanPangkat(Sdmmodels $sdm): array
    {
        $digit = preg_replace('/\D/', '', $sdm->nip ?? '');

        if (strlen($digit) !== 18) {
            return $this->hasilPangkatTidakLengkap('Format NIP tidak standar (bukan 18 digit).');
        }

        $tahun = (int) substr($digit, 8, 4);
        $bulan = (int) substr($digit, 12, 2);

        if ($bulan < 1 || $bulan > 12) {
            return $this->hasilPangkatTidakLengkap('Segmen bulan pada NIP tidak valid (bukan 01-12) -- kemungkinan kode jalur rekrutmen khusus, bukan TMT CPNS reguler.');
        }

        $tanggalDasar = Carbon::create($tahun, $bulan, 1);
        $tanggalPrediksi = $tanggalDasar->copy();
        while ($tanggalPrediksi->lte(Carbon::now())) {
            $tanggalPrediksi = $tanggalPrediksi->copy()->addYears(4);
        }

        $golonganSaatIni = $this->ekstrakGolongan($sdm->pangkat_golongan);
        $pangkatBerikutnya = $golonganSaatIni ? PkrReferensiPangkat::next($golonganSaatIni) : null;

        $catatan = 'Perkiraan kasar berbasis TMT CPNS dari NIP + siklus reguler 4 tahun, BUKAN data TMT kenaikan pangkat sesungguhnya. Belum memperhitungkan syarat lain (predikat kinerja, dll).';
        if ($golonganSaatIni && !$pangkatBerikutnya) {
            $catatan .= ' Golongan saat ini tidak ditemukan di tabel referensi -- nama pangkat berikutnya tidak dapat ditentukan.';
        } elseif (!$golonganSaatIni) {
            $catatan .= ' Data golongan/ruang pegawai ini kosong atau tidak dapat diekstrak -- nama pangkat berikutnya tidak dapat ditentukan.';
        }

        return [
            'tanggal_prediksi' => $tanggalPrediksi,
            'golongan_saat_ini' => $golonganSaatIni,
            'golongan_berikutnya' => $pangkatBerikutnya->golongan_ruang ?? null,
            'nama_pangkat_berikutnya' => $pangkatBerikutnya->nama_pangkat ?? null,
            'sumber_perhitungan' => 'estimasi_nip',
            'catatan' => $catatan,
        ];
    }

    private function hasilPangkatTidakLengkap(string $alasan): array
    {
        return [
            'tanggal_prediksi' => null,
            'golongan_saat_ini' => null,
            'golongan_berikutnya' => null,
            'nama_pangkat_berikutnya' => null,
            'sumber_perhitungan' => 'data_tidak_lengkap',
            'catatan' => $alasan,
        ];
    }

    /**
     * Ekstrak golongan/ruang (mis. "III/b") dari kolom pangkat_golongan yang formatnya
     * bercampur di data produksi: "Nama Pangkat (III/b)", golongan polos "III/b", atau
     * sampah yang tidak bisa diparse (mis. "VII", "IX", "IId" -- tanpa garis miring).
     */
    private function ekstrakGolongan(?string $pangkatGolongan): ?string
    {
        if (!$pangkatGolongan) {
            return null;
        }

        if (preg_match('/\(([IVX]+\/[A-Za-z])\)/', $pangkatGolongan, $m)) {
            return PkrReferensiPangkat::normalisasiGolongan($m[1]);
        }

        $trim = trim($pangkatGolongan);
        if (preg_match('/^[IVX]+\/[A-Za-z]$/', $trim)) {
            return PkrReferensiPangkat::normalisasiGolongan($trim);
        }

        return null;
    }

    /**
     * Prediksi kenaikan jenjang + status komposit kesiapan pengangkatan.
     */
    public function prediksiKenaikanJenjang(Sdmmodels $sdm): array
    {
        $jenjangKode = $sdm->jenjang_kode;
        $ambangBatas = $jenjangKode ? $this->ambangBatasNaikJenjang($jenjangKode) : null;

        if (!$jenjangKode || !$ambangBatas) {
            return [
                'ak_kumulatif' => $jenjangKode ? $this->hitungAkKumulatif($sdm->id) : 0.0,
                'ambang_batas' => null,
                'status' => 'Data Tidak Lengkap',
                'detail_kondisi' => [
                    'ak_cukup' => false,
                    'ujikom_lulus' => false,
                    'predikat_terpenuhi' => false,
                    'formasi_tersedia' => false,
                ],
            ];
        }

        $akKumulatif = $this->hitungAkKumulatif($sdm->id);
        $akCukup = $akKumulatif >= (float) $ambangBatas['ak_kumulatif_minimal'];

        $ujikomLulus = UjikomHasil::where('status_kelulusan', 'lulus')
            ->whereHas('peserta', fn ($q) => $q->where('pegawai_id', $sdm->id))
            ->whereHas('jadwal', fn ($q) => $q->where('jenjang_tujuan', $ambangBatas['ke_jenjang']))
            ->exists();

        $entriTerakhir = PkrAngkaKreditRiwayat::where('sdm_id', $sdm->id)
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->first();
        $predikatTerpenuhi = $entriTerakhir && in_array($entriTerakhir->predikat_kinerja, ['baik', 'sangat_baik']);

        $formasiTersedia = $this->cekFormasiTersedia($sdm, $ambangBatas['ke_jenjang']);
        $status = $this->tentukanStatusKomposit($akCukup, $ujikomLulus, $predikatTerpenuhi, $formasiTersedia);

        return [
            'ak_kumulatif' => $akKumulatif,
            'ambang_batas' => $ambangBatas,
            'status' => $status,
            'detail_kondisi' => [
                'ak_cukup' => $akCukup,
                'ujikom_lulus' => $ujikomLulus,
                'predikat_terpenuhi' => $predikatTerpenuhi,
                'formasi_tersedia' => $formasiTersedia,
            ],
        ];
    }

    /**
     * Cek sisa formasi (kuota - terisi) untuk JF yang sama dengan pegawai ini, di jenjang
     * tujuan, unit kerja pegawai, tahun berjalan. Pakai Formasijabatan::sisa (accessor
     * yang SUDAH ADA dan sudah dipakai PengangkatanController) -- BUKAN "formasi_final"
     * (itu kolom khusus modul RF-01/usulan PKB, bukan kapasitas slot pegawai eksisting).
     */
    private function cekFormasiTersedia(Sdmmodels $sdm, string $jenjangTujuanKode): bool
    {
        $unitKerjaId = $sdm->unit_kerja_id ?? $sdm->formasi?->unit_kerja_id;
        $namaFormasi = $sdm->formasi?->nama_formasi;
        $suffix = self::PETA_KODE_KE_SUFFIX[$jenjangTujuanKode] ?? null;

        if (!$unitKerjaId || !$namaFormasi || !$suffix) {
            return false;
        }

        $formasiTujuan = Formasijabatan::where('unit_kerja_id', $unitKerjaId)
            ->where('nama_formasi', $namaFormasi)
            ->where('tahun_formasi', now()->year)
            ->whereHas('jenjang', fn ($q) => $q->where('nama_jenjang', 'like', '%' . $suffix))
            ->first();

        return $formasiTujuan ? $formasiTujuan->sisa > 0 : false;
    }

    /**
     * Ladder status komposit -- dipakai baik oleh prediksiKenaikanJenjang() (single-row,
     * halaman show) maupun batchStatusKomposit() (halaman index), supaya logikanya tidak
     * pernah drift antara dua jalur. Lihat catatan gap-fill di dalam.
     */
    private function tentukanStatusKomposit(bool $akCukup, bool $ujikomLulus, bool $predikatTerpenuhi, bool $formasiTersedia): string
    {
        // CATATAN: spec asli ("Semua c,d,e,f terpenuhi -> Siap Diangkat", lalu bullet
        // status lain cuma sebut kombinasi c/d/f) tidak mendefinisikan status untuk 2
        // kombinasi yang ternyata bisa terjadi: (1) predikat_terpenuhi gagal SENDIRIAN
        // padahal c/d/f semua terpenuhi, (2) ak_cukup DAN ujikom_lulus dua-duanya belum.
        // Diisi eksplisit di sini (bukan silent default) -- dilaporkan ke user di summary.
        if ($akCukup && $ujikomLulus && $predikatTerpenuhi && $formasiTersedia) {
            return 'Siap Diangkat';
        }
        if ($akCukup && !$ujikomLulus) {
            return 'Perlu Ujikom';
        }
        if ($ujikomLulus && !$akCukup) {
            return 'AK Kurang';
        }
        if ($akCukup && $ujikomLulus && !$formasiTersedia) {
            return 'Formasi Penuh';
        }
        if ($akCukup && $ujikomLulus && $formasiTersedia && !$predikatTerpenuhi) {
            return 'Predikat Belum Terpenuhi';
        }
        // ak_cukup dan ujikom_lulus dua-duanya belum terpenuhi -- AK jadi syarat dasar
        // yang wajar diselesaikan lebih dulu (ujikom kenaikan jenjang biasanya baru
        // relevan setelah AK cukup), jadi dilabeli "AK Kurang".
        return 'AK Kurang';
    }

    /** Kebalikan Sdmmodels::getJenjangKodeAttribute(), dipakai untuk baris Formasijabatan di batch formasi. */
    private function kodeDariNamaJenjang(?string $namaJenjang): ?string
    {
        if (!$namaJenjang) {
            return null;
        }
        foreach (self::PETA_KODE_KE_SUFFIX as $kode => $suffix) {
            if (str_ends_with(trim($namaJenjang), $suffix)) {
                return $kode;
            }
        }
        return null;
    }

    /**
     * Halaman listing PKR (Bagian 3: shell halaman saja). Tabel diisi via AJAX server-side
     * DataTables (lihat data()) -- TIDAK load 3.940 baris sekaligus lagi (itu penyebab
     * payload ~4MB di Bagian 2). Cuma load data ringan utk dropdown filter + alert.
     */
    public function index(Request $request)
    {
        $peringatanLulusBelumDiangkat = $this->hitungLulusBelumDiangkat();
        $unitKerjaList = \App\Models\UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja']);
        $daftarJenjang = self::PETA_KODE_KE_SUFFIX;

        return view('pkr.index', compact('unitKerjaList', 'daftarJenjang', 'peringatanLulusBelumDiangkat'));
    }

    /**
     * Endpoint AJAX server-side DataTables (Bagian 3). Filter search/unit_kerja_id/jenjang_kode
     * dieksekusi murni via SQL (kolom jenjang_kode fisik dari Task 1). Filter status_komposit
     * TIDAK bisa murni SQL -- lihat catatan di dalam -- jadi jalur berbeda: batch-compute status
     * (reuse batchStatusKomposit() yang SAMA dgn Bagian 2, TANPA duplikasi logic) atas subset yang
     * sudah dipersempit search/unit/jenjang, baru filter+paginate di PHP.
     */
    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $searchValue = trim((string) $request->input('search.value', ''));
        $statusFilter = $request->input('status_komposit');

        $recordsTotal = Sdmmodels::where('aktif', true)->count();

        $baseQuery = Sdmmodels::query()->where('aktif', true);
        if ($request->filled('unit_kerja_id')) {
            $baseQuery->where('unit_kerja_id', $request->input('unit_kerja_id'));
        }
        if ($request->filled('jenjang_kode')) {
            $baseQuery->where('jenjang_kode', $request->input('jenjang_kode'));
        }
        if ($searchValue !== '') {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('nama_lengkap', 'like', "%{$searchValue}%")
                  ->orWhere('nip', 'like', "%{$searchValue}%");
            });
        }

        if ($statusFilter) {
            // CATATAN (lihat laporan): status komposit adalah hasil ladder 4-kondisi (AK/ujikom/
            // predikat/formasi) yang urutannya prioritas -- menerjemahkannya jadi satu WHERE SQL
            // murni (CASE WHEN bersarang + subquery formasi via suffix-mapping PHP-only) beresiko
            // drift dari tentukanStatusKomposit(). Dipilih: batch-compute (query SAMA, method SAMA
            // dgn Bagian 2) atas subset yang SUDAH dipersempit search/unit/jenjang oleh SQL di atas,
            // baru filter+sort+paginate di PHP. Kalau user butuh filter status TANPA unit/jenjang di
            // dataset besar, ini tetap menghitung status utk seluruh subset (bisa 3.940 baris worst
            // case) -- tapi TIDAK mengirim HTML 3.940 baris ke browser (masalah asli Bagian 2),
            // cuma menghitung di server lalu kirim 1 halaman saja.
            $filtered = $baseQuery->with(['formasi.jenjang', 'unitKerja'])->get();
            $batch = $this->batchStatusKomposit($filtered);

            $filtered = $filtered->filter(fn ($s) => ($batch[$s->id]['status'] ?? 'Data Tidak Lengkap') === $statusFilter)->values();
            $recordsFiltered = $filtered->count();

            $filtered = $this->urutkanManual($filtered, $request);
            $page = $filtered->slice($start, $length)->values();

            $rows = $page->map(fn ($sdm) => $this->formatBarisDatatable($sdm, $batch[$sdm->id]['status'] ?? 'Data Tidak Lengkap'));
        } else {
            $recordsFiltered = (clone $baseQuery)->count();

            $sortQuery = clone $baseQuery;
            $this->terapkanSortSql($sortQuery, $request);

            $page = $sortQuery->with(['formasi.jenjang', 'unitKerja'])->skip($start)->take($length)->get();

            $batch = $this->batchStatusKomposit($page); // hanya utk halaman ini (~10-100 baris, bukan 3.940)
            $rows = $page->map(fn ($sdm) => $this->formatBarisDatatable($sdm, $batch[$sdm->id]['status'] ?? 'Data Tidak Lengkap'));
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->values(),
        ]);
    }

    /** Sort SQL native -- dipakai jalur cepat (tanpa filter status_komposit). Index kolom HARUS sinkron dgn urutan <th> di pkr/index.blade.php. */
    private function terapkanSortSql($query, Request $request): void
    {
        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        if ($orderColumnIndex === 3) {
            // Unit Kerja: sort by nama_unit_kerja via join (kolom id saja tidak berarti apa-apa utk user)
            $query->leftJoin('unit_kerja', 'sumber_daya_manusia.unit_kerja_id', '=', 'unit_kerja.id')
                ->orderBy('unit_kerja.nama_unit_kerja', $orderDir)
                ->select('sumber_daya_manusia.*');
            return;
        }

        $kolomBisaDiurutkan = [1 => 'nama_lengkap', 2 => 'nip', 4 => 'jenjang_kode'];
        $query->orderBy($kolomBisaDiurutkan[$orderColumnIndex] ?? 'nama_lengkap', $orderDir);
    }

    /** Sort manual (Collection) -- dipakai jalur filter status_komposit, karena data sudah di PHP. */
    private function urutkanManual(\Illuminate\Support\Collection $sdmList, Request $request): \Illuminate\Support\Collection
    {
        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $desc = $request->input('order.0.dir') === 'desc';

        if ($orderColumnIndex === 3) {
            return $sdmList->sortBy(fn ($s) => $s->unitKerja->nama_unit_kerja ?? $s->formasi?->unitKerja?->nama_unit_kerja ?? '', SORT_REGULAR, $desc)->values();
        }

        $kolomBisaDiurutkan = [1 => 'nama_lengkap', 2 => 'nip', 4 => 'jenjang_kode'];
        $kolom = $kolomBisaDiurutkan[$orderColumnIndex] ?? 'nama_lengkap';

        return $sdmList->sortBy($kolom, SORT_REGULAR, $desc)->values();
    }

    /** Format satu baris SDM jadi array siap-JSON untuk response DataTables. */
    private function formatBarisDatatable(Sdmmodels $sdm, string $status): array
    {
        $pangkat = $this->prediksiKenaikanPangkat($sdm);
        $dekat = $pangkat['tanggal_prediksi'] && $pangkat['tanggal_prediksi']->lte(now()->addMonths(3));
        $unitNama = $sdm->unitKerja->nama_unit_kerja ?? $sdm->formasi?->unitKerja?->nama_unit_kerja ?? '-';

        $warna = match ($status) {
            'Siap Diangkat' => ['bg' => '#d1fae5', 'fg' => '#065f46'],
            'Perlu Ujikom', 'AK Kurang', 'Predikat Belum Terpenuhi' => ['bg' => '#fef3c7', 'fg' => '#92400e'],
            'Formasi Penuh' => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
            default => ['bg' => '#e5e7eb', 'fg' => '#374151'],
        };

        $checkboxHtml = $dekat ? '<input type="checkbox" class="chkSdm" value="' . $sdm->id . '">' : '';

        $prediksiHtml = $pangkat['tanggal_prediksi']
            ? e($pangkat['tanggal_prediksi']->format('d-m-Y')) . '<span class="do-badge d-block mt-1" style="background:#e0e7ff; color:#3730a3; width:fit-content;">Estimasi dari NIP</span>'
            : '<span class="text-muted">Data tidak lengkap</span>';

        return [
            'checkbox' => $checkboxHtml,
            'dekat' => $dekat,
            'nama_lengkap' => e($sdm->nama_lengkap),
            'nip' => e($sdm->nip ?? '-'),
            'unit_kerja' => e($unitNama),
            'jenjang' => e($sdm->jenjang_nama ?? '-'),
            'prediksi_pangkat' => $prediksiHtml,
            'status_komposit' => '<span class="do-badge" style="background:' . $warna['bg'] . '; color:' . $warna['fg'] . ';">' . e($status) . '</span>',
            'aksi' => '<a href="' . route('user.pkr.show', $sdm->id) . '" class="btn btn-sm btn-outline-primary" title="Detail Karir"><i class="fas fa-id-card"></i></a>',
        ];
    }

    /**
     * Batch: AK kumulatif + predikat terakhir + ujikom lulus + formasi sisa untuk SEMUA
     * pegawai di $sdmList sekaligus (bukan N+1). Return [sdm_id => ['ak_kumulatif'=>, 'status'=>]].
     */
    private function batchStatusKomposit(\Illuminate\Support\Collection $sdmList): array
    {
        $sdmIds = $sdmList->pluck('id');

        // 1) AK kumulatif per sdm_id
        $akMap = PkrAngkaKreditRiwayat::whereIn('sdm_id', $sdmIds)
            ->selectRaw('sdm_id, SUM(angka_kredit_diperoleh) as total')
            ->groupBy('sdm_id')
            ->pluck('total', 'sdm_id');

        // 2) Predikat entri terakhir per sdm_id (via MAX(id) per grup)
        $latestIds = PkrAngkaKreditRiwayat::whereIn('sdm_id', $sdmIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('sdm_id')
            ->pluck('id');
        $predikatMap = PkrAngkaKreditRiwayat::whereIn('id', $latestIds)
            ->pluck('predikat_kinerja', 'sdm_id');

        // 3) Pasangan (sdm_id, jenjang_tujuan) yang sudah lulus ujikom
        $ujikomLulusPairs = UjikomHasil::where('status_kelulusan', 'lulus')
            ->with(['peserta:id,pegawai_id', 'jadwal:id,jenjang_tujuan'])
            ->get()
            ->map(fn ($h) => ['pegawai_id' => $h->peserta?->pegawai_id, 'jenjang_tujuan' => $h->jadwal?->jenjang_tujuan])
            ->filter(fn ($p) => $p['pegawai_id'] && $p['jenjang_tujuan'])
            ->values();

        // 4) Semua formasi_jabatan tahun berjalan + terisi (utk cek sisa), dipetakan by unit+nama_formasi+jenjang_kode
        $formasiMap = [];
        $formasiRows = Formasijabatan::with('jenjang')
            ->withCount(['sdmAktif as terisi'])
            ->where('tahun_formasi', now()->year)
            ->get();
        foreach ($formasiRows as $f) {
            $kode = $this->kodeDariNamaJenjang($f->jenjang->nama_jenjang ?? null);
            if (!$kode) {
                continue;
            }
            $key = $f->unit_kerja_id . '|' . $f->nama_formasi . '|' . $kode;
            $formasiMap[$key] = ($formasiMap[$key] ?? 0) + $f->sisa;
        }

        $result = [];
        foreach ($sdmList as $sdm) {
            $jenjangKode = $sdm->jenjang_kode;
            $ambangBatas = $jenjangKode ? $this->ambangBatasNaikJenjang($jenjangKode) : null;
            $akKumulatif = (float) ($akMap[$sdm->id] ?? 0);

            if (!$jenjangKode || !$ambangBatas) {
                $result[$sdm->id] = ['ak_kumulatif' => $akKumulatif, 'status' => 'Data Tidak Lengkap'];
                continue;
            }

            $akCukup = $akKumulatif >= (float) $ambangBatas['ak_kumulatif_minimal'];
            $ujikomLulus = $ujikomLulusPairs->contains(fn ($row) => $row['pegawai_id'] == $sdm->id && $row['jenjang_tujuan'] === $ambangBatas['ke_jenjang']);
            $predikat = $predikatMap[$sdm->id] ?? null;
            $predikatTerpenuhi = in_array($predikat, ['baik', 'sangat_baik']);

            $unitKerjaId = $sdm->unit_kerja_id ?? $sdm->formasi?->unit_kerja_id;
            $namaFormasi = $sdm->formasi?->nama_formasi;
            $key = $unitKerjaId . '|' . $namaFormasi . '|' . $ambangBatas['ke_jenjang'];
            $formasiTersedia = ($formasiMap[$key] ?? 0) > 0;

            $status = $this->tentukanStatusKomposit($akCukup, $ujikomLulus, $predikatTerpenuhi, $formasiTersedia);

            $result[$sdm->id] = ['ak_kumulatif' => $akKumulatif, 'status' => $status];
        }

        return $result;
    }

    /**
     * Jumlah pegawai yang lulus ujikom >6 bulan lalu tapi belum ada record
     * pengangkatan_kandidat yang cocok (via ujikom_hasil_id) -- alert Task 4.
     * Tidak ada relasi inverse UjikomHasil->kandidatPengangkatan di model asli, jadi
     * dicek langsung lewat whereNotIn (bukan menambah relasi baru ke model di luar scope PKR).
     */
    private function hitungLulusBelumDiangkat(): int
    {
        $sudahDiangkatIds = \App\Models\PengangkatanKandidat::whereNotNull('ujikom_hasil_id')->pluck('ujikom_hasil_id');

        return UjikomHasil::where('status_kelulusan', 'lulus')
            ->where('tanggal_ujian', '<=', now()->subMonths(6))
            ->whereNotIn('id', $sudahDiangkatIds)
            ->count();
    }

    /**
     * Export Excel "Working List" kenaikan pangkat untuk sdm_id terpilih.
     */
    public function exportWorkingList(Request $request)
    {
        $request->validate(['sdm_ids' => 'required|array', 'sdm_ids.*' => 'integer']);

        $sdmList = Sdmmodels::with(['formasi.jenjang', 'unitKerja'])->whereIn('id', $request->sdm_ids)->get();
        foreach ($sdmList as $sdm) {
            $sdm->pkr_pangkat = $this->prediksiKenaikanPangkat($sdm);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PkrWorkingListExport($sdmList),
            'working-list-kenaikan-pangkat-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
