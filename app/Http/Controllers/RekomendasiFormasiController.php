<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\RekomendasiFormasiUsulan;
use App\Models\RekomendasiFormasiVariabel;
use App\Models\RekomendasiFormasiHasil;
use App\Models\RekomendasiFormasiPegawaiExisting;
use App\Models\RekomendasiFormasiBeritaAcara;
use App\Models\RekomendasiFormasiSurat;
use App\Models\FormulaRfMaster;
use App\Models\UnitKerja;
use App\Models\Formasijabatan;
use App\Models\JenjangJabatan;
use App\Models\Sdmmodels;

class RekomendasiFormasiController extends Controller
{
    /** Daftar JF yang rumusnya sudah tersedia di sistem. JF lain ditampilkan disabled di form. */
    private const JF_TERSEDIA = ['pkb' => 'Penguji Kendaraan Bermotor (PKB)'];

    /** 22 JF resmi (CLAUDE.md) di luar PKB -- ditampilkan disabled "rumus belum tersedia". */
    private const JF_BELUM_TERSEDIA = [
        'Pengawas Keselamatan Pelayaran',
        'Teknisi Penerbangan',
        'Asisten Inspektur Angkutan Udara',
        'Inspektur Angkutan Udara',
        'Asisten Inspektur Bandar Udara',
        'Inspektur Bandar Udara',
        'Asisten Inspektur Keamanan Penerbangan',
        'Inspektur Keamanan Penerbangan',
        'Asisten Inspektur Navigasi Penerbangan',
        'Inspektur Navigasi Penerbangan',
        'Asisten Inspektur Kelaikudaraan Pesawat Udara',
        'Inspektur Kelaikudaraan Pesawat Udara',
        'Asisten Inspektur Pengoperasian Pesawat Udara',
        'Inspektur Pengoperasian Pesawat Udara',
        'Penguji Sarana Perkeretaapian',
        'Penguji Prasarana Perkeretaapian',
        'Inspektur Sarana Perkeretaapian',
        'Inspektur Prasarana Perkeretaapian',
        'Auditor Perkeretaapian',
        'Asisten Penguji Sarana Perkeretaapian',
        'Asisten Penguji Prasarana Perkeretaapian',
    ];

    private const JENJANG_LIST = ['pemula', 'terampil', 'mahir', 'penyelia'];

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = RekomendasiFormasiUsulan::with(['unitKerja', 'pengaju'])->withCount('hasil');

        if ($user->hasRole('admin_unit')) {
            $query->where('unit_kerja_id', $user->unit_kerja_id);
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $usulanList = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $tahunList = RekomendasiFormasiUsulan::select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');

        return view('rekomendasi_formasi.index', compact('usulanList', 'tahunList'));
    }

    public function create()
    {
        $user = Auth::user();

        $unitKerja = $user->hasRole('admin_unit') ? $user->unitKerja : null;
        $unitKerjaList = $user->hasRole('admin_unit')
            ? collect()
            : UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja', 'jenis_instansi']);

        return view('rekomendasi_formasi.create', [
            'unitKerja' => $unitKerja,
            'unitKerjaList' => $unitKerjaList,
            'jfTersedia' => self::JF_TERSEDIA,
            'jfBelumTersedia' => self::JF_BELUM_TERSEDIA,
            'jenjangList' => self::JENJANG_LIST,
            'tahunDefault' => date('Y') + 1,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'kode_jf' => 'required|in:pkb',
            'tahun' => 'required|integer|min:' . date('Y'),
            'unit_kerja_id' => $user->hasRole('admin_unit') ? 'nullable' : 'required|exists:unit_kerja,id',
            'jumlah_kbwu' => 'required|integer|min:0',
            'uji_pertama' => 'required|integer|min:0',
            'uji_reguler' => 'required|integer|min:0',
            'numpang_uji_masuk' => 'nullable|integer|min:0',
            'numpang_uji_keluar' => 'nullable|integer|min:0',
            'mutasi_masuk' => 'nullable|integer|min:0',
            'mutasi_keluar' => 'nullable|integer|min:0',
            'bbm_bensin' => 'nullable|integer|min:0',
            'bbm_solar' => 'nullable|integer|min:0',
            'hari_kerja' => 'nullable|in:5,6',
            'usulan_admin_unit' => 'nullable|array',
            'usulan_admin_unit.*' => 'nullable|integer|min:0',
            'pegawai' => 'nullable|array',
            'pegawai.*.nama' => 'nullable|string|max:150',
            'pegawai.*.nip' => 'nullable|string|max:50',
            'pegawai.*.jenjang' => 'nullable|in:' . implode(',', self::JENJANG_LIST),
        ]);

        // Unit kerja: admin_unit terkunci ke unitnya sendiri; admin/super_admin
        // pilih lewat dropdown (skenario "membantu input" -- lihat Bagian 2).
        $unitKerja = $user->hasRole('admin_unit')
            ? $user->unitKerja
            : UnitKerja::findOrFail($request->unit_kerja_id);

        if (!$unitKerja) {
            return back()->withInput()->with('error', 'Unit kerja tidak ditemukan / akun Anda belum terhubung ke unit kerja manapun.');
        }

        if (empty($unitKerja->jenis_instansi)) {
            return back()->withInput()->with('error', "Unit kerja \"{$unitKerja->nama_unit_kerja}\" belum punya data Jenis Instansi (Kemenhub/Dishub). Hubungi Admin Pusbin untuk melengkapi data ini terlebih dahulu.");
        }

        $usulan = null;

        DB::transaction(function () use ($request, $unitKerja, $user, &$usulan) {
            $usulan = RekomendasiFormasiUsulan::create([
                'kode_jf' => $request->kode_jf,
                'unit_kerja_id' => $unitKerja->id,
                'jenis_instansi' => $unitKerja->jenis_instansi,
                'tahun' => $request->tahun,
                'diajukan_oleh' => $user->id,
                'status' => 'draft',
            ]);

            $variabel = RekomendasiFormasiVariabel::create([
                'usulan_id' => $usulan->id,
                'jumlah_kbwu' => $request->jumlah_kbwu,
                'uji_pertama' => $request->uji_pertama,
                'uji_reguler' => $request->uji_reguler,
                'numpang_uji_masuk' => $request->numpang_uji_masuk ?? 0,
                'numpang_uji_keluar' => $request->numpang_uji_keluar ?? 0,
                'mutasi_masuk' => $request->mutasi_masuk ?? 0,
                'mutasi_keluar' => $request->mutasi_keluar ?? 0,
                'bbm_bensin' => $request->bbm_bensin ?? 0,
                'bbm_solar' => $request->bbm_solar ?? 0,
                'hari_kerja' => $request->hari_kerja ?? '5',
            ]);

            $bezettingPerJenjang = array_fill_keys(self::JENJANG_LIST, 0);

            if ($unitKerja->jenis_instansi === 'dishub' && $request->has('pegawai')) {
                $bezettingPerJenjang = $this->simpanPegawaiDishub($request->pegawai, $usulan, $unitKerja);
            } else {
                $bezettingPerJenjang = $this->hitungBezettingKemenhub($unitKerja, $request->kode_jf);
            }

            foreach (self::JENJANG_LIST as $jenjang) {
                $hasil = $this->hitungKebutuhanFormasi($request->kode_jf, $jenjang, $variabel);
                $bezetting = $bezettingPerJenjang[$jenjang];
                $formasiSistem = max(0, $hasil['kebutuhan_bulat'] - $bezetting);

                RekomendasiFormasiHasil::create([
                    'usulan_id' => $usulan->id,
                    'jenjang' => $jenjang,
                    'total_wpv' => $hasil['total_wpv'],
                    'kebutuhan_raw' => $hasil['kebutuhan_raw'],
                    'kebutuhan_bulat' => $hasil['kebutuhan_bulat'],
                    'bezetting' => $bezetting,
                    'formasi_sistem' => $formasiSistem,
                    'formasi_final' => $formasiSistem,
                    'usulan_admin_unit' => $request->input("usulan_admin_unit.{$jenjang}"),
                ]);
            }

            $usulan->update(['status' => 'diajukan']);
        });

        return redirect()->route('user.rekomendasi-formasi.show', $usulan->id)
            ->with('success', 'Usulan berhasil dibuat dan dihitung otomatis.');
    }

    public function show($id)
    {
        $usulan = RekomendasiFormasiUsulan::with([
            'unitKerja', 'pengaju', 'variabel', 'hasil', 'pegawaiExisting',
            'beritaAcara.ttdPusbinOleh', 'beritaAcara.ttdPengusulOleh', 'surat',
        ])->findOrFail($id);

        $this->authorizeAkses($usulan);

        // Mapping status -> index step untuk stepper (Bagian 6, RF-1C).
        // 'menunggu_verifikasi' disamakan dg 'diajukan' (belum ada alur yang
        // men-set status itu secara aktif saat ini, disiapkan forward-compatible).
        // 'ba_selesai' disamakan dg 'menunggu_ttd_rekomendasi' (step "BA
        // Ditandatangani" sudah genap, tinggal menunggu admin menerbitkan surat).
        $statusIndexMap = [
            'draft' => 0,
            'diajukan' => 1,
            'menunggu_verifikasi' => 1,
            'verifikasi_disepakati' => 2,
            'menunggu_ttd_ba' => 3,
            'ba_selesai' => 4,
            'menunggu_ttd_rekomendasi' => 4,
            'selesai' => 5,
        ];
        $stepLabels = ['Draft', 'Diajukan', 'Verifikasi Disepakati', 'BA Ditandatangani (2 Pihak)', 'Menunggu TTD Rekomendasi', 'Selesai'];
        $currentStatusIndex = $statusIndexMap[$usulan->status] ?? 0;

        return view('rekomendasi_formasi.show', compact('usulan', 'stepLabels', 'currentStatusIndex'));
    }

    public function edit($id)
    {
        $usulan = RekomendasiFormasiUsulan::with(['unitKerja', 'variabel', 'hasil'])->findOrFail($id);

        $this->authorizeAkses($usulan);

        // RF-1C Bagian 2: data variabel usulan dikunci begitu status masuk
        // verifikasi_disepakati atau lebih lanjut -- hanya bisa dibuka lagi
        // lewat kembalikanKeDraft() (override eksplisit Admin Pusbin + alasan).
        if (!in_array($usulan->status, ['draft', 'diajukan'])) {
            return back()->with('error', 'Usulan yang sudah diverifikasi tidak dapat diedit lagi. Admin Pusbin dapat mengembalikan status ke Draft (kasus khusus) jika perlu revisi.');
        }

        return view('rekomendasi_formasi.edit', [
            'usulan' => $usulan,
            'jenjangList' => self::JENJANG_LIST,
        ]);
    }

    /**
     * Update variabel beban kerja & jalankan ulang kalkulasi. Data pegawai yang
     * sudah masuk sistem (Dishub) TIDAK diubah lewat sini -- itu perubahan
     * permanen di modul Pegawai JFT, bukan bagian dari revisi usulan.
     */
    public function update(Request $request, $id)
    {
        $usulan = RekomendasiFormasiUsulan::with(['unitKerja', 'variabel'])->findOrFail($id);
        $this->authorizeAkses($usulan);

        // RF-1C Bagian 2: data variabel usulan dikunci begitu status masuk
        // verifikasi_disepakati atau lebih lanjut -- hanya bisa dibuka lagi
        // lewat kembalikanKeDraft() (override eksplisit Admin Pusbin + alasan).
        if (!in_array($usulan->status, ['draft', 'diajukan'])) {
            return back()->with('error', 'Usulan yang sudah diverifikasi tidak dapat diedit lagi. Admin Pusbin dapat mengembalikan status ke Draft (kasus khusus) jika perlu revisi.');
        }

        $request->validate([
            'jumlah_kbwu' => 'required|integer|min:0',
            'uji_pertama' => 'required|integer|min:0',
            'uji_reguler' => 'required|integer|min:0',
            'numpang_uji_masuk' => 'nullable|integer|min:0',
            'numpang_uji_keluar' => 'nullable|integer|min:0',
            'mutasi_masuk' => 'nullable|integer|min:0',
            'mutasi_keluar' => 'nullable|integer|min:0',
            'bbm_bensin' => 'nullable|integer|min:0',
            'bbm_solar' => 'nullable|integer|min:0',
            'hari_kerja' => 'nullable|in:5,6',
            'usulan_admin_unit' => 'nullable|array',
            'usulan_admin_unit.*' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $usulan) {
            $usulan->variabel->update([
                'jumlah_kbwu' => $request->jumlah_kbwu,
                'uji_pertama' => $request->uji_pertama,
                'uji_reguler' => $request->uji_reguler,
                'numpang_uji_masuk' => $request->numpang_uji_masuk ?? 0,
                'numpang_uji_keluar' => $request->numpang_uji_keluar ?? 0,
                'mutasi_masuk' => $request->mutasi_masuk ?? 0,
                'mutasi_keluar' => $request->mutasi_keluar ?? 0,
                'bbm_bensin' => $request->bbm_bensin ?? 0,
                'bbm_solar' => $request->bbm_solar ?? 0,
                'hari_kerja' => $request->hari_kerja ?? '5',
            ]);

            $variabel = $usulan->variabel->fresh();
            $unitKerja = $usulan->unitKerja;

            $bezettingPerJenjang = $unitKerja->jenis_instansi === 'dishub'
                ? $this->hitungBezettingDariRfPegawai($usulan)
                : $this->hitungBezettingKemenhub($unitKerja, $usulan->kode_jf);

            foreach (self::JENJANG_LIST as $jenjang) {
                $hasil = $this->hitungKebutuhanFormasi($usulan->kode_jf, $jenjang, $variabel);
                $bezetting = $bezettingPerJenjang[$jenjang];
                $formasiSistem = max(0, $hasil['kebutuhan_bulat'] - $bezetting);

                // Kalau Pusbin sudah pernah override formasi_final secara manual
                // (lewat updateHasilFinal()) SEBELUM variabel beban kerja ini
                // diedit ulang, jangan diam-diam ditimpa balik ke nilai sistem --
                // pertahankan override-nya. Hanya reset formasi_final = sistem
                // kalau memang belum pernah di-override (baris baru, atau
                // formasi_final masih persis sama dengan formasi_sistem lama).
                $hasilLama = $usulan->hasil()->where('jenjang', $jenjang)->first();
                $formasiFinal = ($hasilLama && $hasilLama->formasi_final !== $hasilLama->formasi_sistem)
                    ? $hasilLama->formasi_final
                    : $formasiSistem;

                $usulan->hasil()->updateOrCreate(
                    ['jenjang' => $jenjang],
                    [
                        'total_wpv' => $hasil['total_wpv'],
                        'kebutuhan_raw' => $hasil['kebutuhan_raw'],
                        'kebutuhan_bulat' => $hasil['kebutuhan_bulat'],
                        'bezetting' => $bezetting,
                        'formasi_sistem' => $formasiSistem,
                        'formasi_final' => $formasiFinal,
                        'usulan_admin_unit' => $request->input("usulan_admin_unit.{$jenjang}"),
                    ]
                );
            }
        });

        return redirect()->route('user.rekomendasi-formasi.show', $usulan->id)
            ->with('success', 'Usulan berhasil diperbarui dan dihitung ulang.');
    }

    /**
     * Override manual angka Formasi Final per jenjang -- KHUSUS Pusbin (admin,
     * super_admin, kabid_perencanaan_jft; diblokir eksplisit utk admin_unit
     * walau middleware route grup mengizinkan role itu utk method lain di
     * controller ini). Dipakai kalau pimpinan menghendaki ROUNDDOWN (atau
     * angka lain) utk jenjang tertentu, alih-alih default sistem ROUNDUP --
     * lihat kebutuhan_raw (angka sebelum dibulatkan) di halaman detail
     * sebagai acuan sebelum override.
     *
     * formasi_sistem TIDAK PERNAH diubah lewat sini -- itu audit trail hasil
     * murni sistem (kebutuhan_bulat - bezetting). Yang diubah hanya
     * formasi_final, kolom yang memang didesain sejak awal (RF-1A) sebagai
     * "bisa diedit manual Admin Pusbin, defaultnya = formasi_sistem".
     *
     * RF-1C Bagian 2: boleh diedit sampai KAPANPUN sebelum Berita Acara
     * ditandatangani kedua pihak (status ba_selesai) -- BUKAN cuma sampai
     * draft/diajukan seperti method update() (variabel beban kerja). Setelah
     * ba_selesai/menunggu_ttd_rekomendasi/selesai, angka final sudah dipakai
     * sbg dasar Berita Acara & (kalau sudah selesai) sudah menambah kuota
     * Formasi -- tidak boleh berubah lagi tanpa override eksplisit lewat
     * kembalikanKeDraft().
     */
    public function updateHasilFinal(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->hasRole('admin_unit')) {
            abort(403, 'Admin Unit tidak berwenang mengubah angka Formasi Final.');
        }

        $usulan = RekomendasiFormasiUsulan::with('hasil')->findOrFail($id);

        if (in_array($usulan->status, ['ba_selesai', 'menunggu_ttd_rekomendasi', 'selesai'])) {
            return back()->with('error', 'Formasi Final tidak bisa diubah lagi -- Berita Acara sudah ditandatangani kedua pihak.');
        }

        $request->validate([
            'formasi_final' => 'required|array',
            'formasi_final.*' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $usulan) {
            foreach (self::JENJANG_LIST as $jenjang) {
                $nilai = $request->input("formasi_final.{$jenjang}");
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                $usulan->hasil()->where('jenjang', $jenjang)->update(['formasi_final' => (int) $nilai]);
            }
        });

        return redirect()->route('user.rekomendasi-formasi.show', $usulan->id)
            ->with('success', 'Angka Formasi Final berhasil diperbarui.');
    }

    /**
     * Tandai hasil pertemuan verifikasi (Zoom/tatap muka, di LUAR sistem)
     * antara Pusbin & unit pengusul sebagai disepakati. Sistem hanya mencatat
     * HASIL kesepakatan, tidak menjadwalkan/memfasilitasi pertemuannya sendiri.
     * Membuat record Berita Acara (nomor otomatis, tanggal = saat ini).
     */
    public function tandaiVerifikasiDisepakati(Request $request, $id)
    {
        $usulan = RekomendasiFormasiUsulan::findOrFail($id);

        if (!in_array($usulan->status, ['diajukan', 'menunggu_verifikasi'])) {
            return back()->with('error', 'Usulan tidak dalam status yang bisa diverifikasi.');
        }

        DB::transaction(function () use ($usulan) {
            $usulan->update(['status' => 'verifikasi_disepakati']);

            RekomendasiFormasiBeritaAcara::create([
                'usulan_id' => $usulan->id,
                'nomor_ba' => $this->generateNomorBA(),
                'tanggal_verifikasi' => now(),
            ]);
        });

        return back()->with('success', 'Verifikasi ditandai selesai. Berita Acara siap diterbitkan.');
    }

    /**
     * Rekam tanda tangan digital Opsi B (BUKAN TTE bersertifikat) -- jejak
     * audit nama, jabatan/role, waktu, IP. Pihak 1 (Pusbin) HANYA role
     * kabid_perencanaan_jft yang berwenang -- sengaja TIDAK termasuk
     * admin/super_admin, karena TTD ini merepresentasikan identitas personal
     * Kepala Bidang, bukan hak administratif umum.
     */
    public function tandaTanganBA(Request $request, $id)
    {
        $usulan = RekomendasiFormasiUsulan::findOrFail($id);
        $ba = $usulan->beritaAcara;
        $user = Auth::user();

        if (!$ba) {
            return back()->with('error', 'Berita Acara belum dibuat.');
        }

        if ($usulan->status !== 'verifikasi_disepakati' && $usulan->status !== 'menunggu_ttd_ba') {
            return back()->with('error', 'Berita Acara belum siap ditandatangani.');
        }

        if ($user->hasRole('kabid_perencanaan_jft')) {
            if ($ba->ttd_pusbin_oleh) {
                return back()->with('error', 'Pihak Pusbin sudah menandatangani sebelumnya.');
            }
            $ba->update([
                'ttd_pusbin_oleh' => $user->id,
                'ttd_pusbin_at' => now(),
                'ttd_pusbin_ip' => $request->ip(),
            ]);
        } elseif ($user->hasRole('admin_unit') && $user->unit_kerja_id == $usulan->unit_kerja_id) {
            if ($ba->ttd_pengusul_oleh) {
                return back()->with('error', 'Pihak pengusul sudah menandatangani sebelumnya.');
            }
            $ba->update([
                'ttd_pengusul_oleh' => $user->id,
                'ttd_pengusul_at' => now(),
                'ttd_pengusul_ip' => $request->ip(),
            ]);
        } else {
            abort(403, 'Anda tidak berwenang menandatangani Berita Acara ini.');
        }

        $usulan->update(['status' => 'menunggu_ttd_ba']);

        $ba->refresh();
        if ($ba->ttd_pusbin_oleh && $ba->ttd_pengusul_oleh) {
            $usulan->update(['status' => 'ba_selesai']);
        }

        return back()->with('success', 'Tanda tangan digital berhasil direkam.');
    }

    /**
     * Cetak PDF Berita Acara (format Lampiran IV PM 4/2024, disesuaikan 2
     * pihak penandatangan). Bisa dicetak ulang kapan saja setelah BA dibuat
     * (termasuk sebelum/sesudah ditandatangani) -- pola sama seperti
     * PengangkatanController::generateSurat() yang bisa diunduh ulang.
     */
    public function cetakBeritaAcara($id)
    {
        $usulan = RekomendasiFormasiUsulan::with([
            'unitKerja', 'hasil', 'beritaAcara.ttdPusbinOleh', 'beritaAcara.ttdPengusulOleh',
        ])->findOrFail($id);

        $this->authorizeAkses($usulan);

        if (!$usulan->beritaAcara) {
            return back()->with('error', 'Berita Acara belum dibuat untuk usulan ini.');
        }

        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ])
        ->setPaper('a4', 'portrait')
        ->loadView('rekomendasi_formasi.pdf.berita_acara', ['usulan' => $usulan, 'ba' => $usulan->beritaAcara]);

        $filename = 'berita_acara_' . str_replace('/', '-', $usulan->beritaAcara->nomor_ba ?? $usulan->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Terbitkan Surat Rekomendasi Formasi: generate+download PDF sekaligus
     * transisi status ba_selesai -> menunggu_ttd_rekomendasi -- pola IDENTIK
     * PengangkatanController::generateSurat() (1 request GET melakukan
     * keduanya, bisa diunduh ulang berkali-kali tanpa duplikasi status/record).
     * TTD Kapusbin JFT tetap manual/fisik (belum ada TTE resmi), sama seperti
     * Surat Rekomendasi Pengangkatan JFT saat ini.
     */
    public function terbitkanSuratRekomendasi($id)
    {
        $usulan = RekomendasiFormasiUsulan::with(['unitKerja', 'hasil', 'surat'])->findOrFail($id);

        abort_unless(in_array($usulan->status, ['ba_selesai', 'menunggu_ttd_rekomendasi']), 403);

        if ($usulan->status === 'ba_selesai') {
            $usulan->update(['status' => 'menunggu_ttd_rekomendasi']);
        }

        if (!$usulan->surat) {
            RekomendasiFormasiSurat::create([
                'usulan_id' => $usulan->id,
                'tanggal_surat' => now()->toDateString(),
            ]);
            $usulan->load('surat');
        }

        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ])
        ->setPaper('a4', 'portrait')
        ->loadView('rekomendasi_formasi.pdf.surat_rekomendasi', ['usulan' => $usulan]);

        $filename = 'surat_rekomendasi_formasi_' . $usulan->id . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Konfirmasi surat rekomendasi sudah ditandatangani fisik: menunggu_ttd_rekomendasi
     * -> selesai. Sekaligus menambahkan formasi_final ke kuota Formasi
     * existing per jenjang (increment, BUKAN overwrite -- kuota lama tetap
     * dihitung, konsisten dg cara kerja modul Formasi yang selalu akumulatif).
     */
    public function konfirmasiTtdRekomendasi($id)
    {
        $usulan = RekomendasiFormasiUsulan::with(['hasil', 'surat'])->findOrFail($id);

        abort_unless($usulan->status === 'menunggu_ttd_rekomendasi', 403);

        DB::transaction(function () use ($usulan) {
            if ($usulan->surat) {
                $usulan->surat->update(['ditandatangani' => true]);
            }

            $usulan->update(['status' => 'selesai']);

            $namaFormasi = 'Penguji Kendaraan Bermotor'; // satu-satunya JF dg rumus tersedia saat ini (PKB)

            foreach ($usulan->hasil as $h) {
                if ($h->formasi_final <= 0) {
                    continue; // tidak ada tambahan formasi utk jenjang ini, jangan bikin baris kosong
                }

                // Jabatan+jenjang PKB dirujuk PERSIS sama seperti pola yang
                // sudah dipakai hitungBezettingKemenhub()/cariFormasiJabatanPkb()
                // di RF-1B: jenjang_jabatan.nama_jenjang = "{nama_formasi} {Jenjang}".
                $jenjangJabatan = JenjangJabatan::where('nama_jenjang', $namaFormasi . ' ' . ucfirst($h->jenjang))->first();
                if (!$jenjangJabatan) {
                    continue; // seharusnya tidak terjadi, defensif
                }

                $formasi = Formasijabatan::firstOrCreate(
                    [
                        'unit_kerja_id' => $usulan->unit_kerja_id,
                        'nama_formasi' => $namaFormasi,
                        'jenjang_id' => $jenjangJabatan->id,
                        'tahun_formasi' => (string) $usulan->tahun,
                    ],
                    ['kuota' => 0]
                );

                $formasi->increment('kuota', $h->formasi_final);
            }
        });

        return redirect()->route('user.rekomendasi-formasi.show', $id)
            ->with('success', 'Surat Rekomendasi Formasi selesai, kuota Formasi telah diperbarui.');
    }

    /**
     * Override eksplisit Admin Pusbin: kembalikan status ke draft supaya data
     * variabel/hasil bisa diedit ulang, meski sudah lewat tahap verifikasi.
     * WAJIB isi alasan (audit trail sederhana, RF-1C Bagian 2). TIDAK bisa
     * dipakai kalau usulan sudah 'selesai' -- di titik itu formasi_final
     * sudah menambah kuota Formasi sungguhan, membatalkannya butuh proses
     * manual terpisah (di luar scope), bukan sekadar ganti status.
     */
    public function kembalikanKeDraft(Request $request, $id)
    {
        $usulan = RekomendasiFormasiUsulan::findOrFail($id);

        if ($usulan->status === 'selesai') {
            return back()->with('error', 'Usulan yang sudah Selesai tidak bisa dikembalikan ke Draft -- kuota Formasi sudah bertambah. Hubungi Admin Pusbin untuk penanganan manual.');
        }

        if ($usulan->status === 'draft') {
            return back()->with('error', 'Usulan sudah berstatus Draft.');
        }

        $request->validate([
            'alasan' => 'required|string|min:5|max:1000',
        ]);

        $user = Auth::user();
        $catatan = "[{$user->name}, " . now()->format('d-m-Y H:i') . "] Dikembalikan ke Draft dari status \"{$usulan->label_status}\". Alasan: {$request->alasan}";

        $usulan->update([
            'status' => 'draft',
            'catatan_override' => trim(($usulan->catatan_override ? $usulan->catatan_override . "\n\n" : '') . $catatan),
        ]);

        return redirect()->route('user.rekomendasi-formasi.show', $usulan->id)
            ->with('success', 'Usulan dikembalikan ke status Draft. Data bisa diedit ulang.');
    }

    /**
     * Generate nomor Berita Acara -- format & helper konsisten dg konvensi
     * penomoran surat yang sudah ada di project (lihat
     * formatNomorPermohonanPengangkatan() di app/helpers.php).
     */
    private function generateNomorBA(): string
    {
        $tahun = now()->year;
        $urut = RekomendasiFormasiBeritaAcara::whereYear('created_at', $tahun)->count() + 1;

        return formatNomorBeritaAcaraRekomendasiFormasi($urut);
    }

    /**
     * Mesin kalkulasi inti -- rumus ΣWpv mengikuti PERSIS logika Excel
     * referensi (diverifikasi match persis 4 desimal utk SEMUA 4 jenjang,
     * data Kabupaten Bandung).
     *
     * PEMBULATAN: ROUNDUP untuk SEMUA jenjang -- ini KEPUTUSAN PRODUK yang
     * disengaja (bukan mengikuti file Excel referensi apa adanya). File Excel
     * sumber sendiri pakai ROUNDUP hanya utk Pemula dan ROUNDDOWN utk
     * Terampil/Mahir/Penyelia (PEMULA_DISHUB!L26 vs TERAMPIL_DISHUB!L105 /
     * MAHIR_DISHUB!M46 / PENYELIA_DISHUB!M39) -- pola itu TIDAK diikuti di
     * sini. Konsekuensi: utk kasus di mana raw mendekati batas bawah bulat
     * berikutnya (co. Kab Bandung: Terampil raw=21.0491, Mahir raw=6.0395,
     * Penyelia raw=5.0047), hasil sistem akan 1 angka lebih tinggi dari yang
     * tertulis di Excel utk 3 jenjang tsb. Nilai RAW (sebelum dibulatkan)
     * tetap disimpan (kebutuhan_raw) dan ditampilkan di halaman detail,
     * supaya pimpinan bisa lihat sendiri angka aslinya dan menimbang perlu
     * override manual atau tidak lewat field formasi_final (lihat
     * updateHasilFinal()).
     */
    private function hitungKebutuhanFormasi(string $kodeJf, string $jenjang, RekomendasiFormasiVariabel $v): array
    {
        $butirKegiatan = FormulaRfMaster::where('kode_jf', $kodeJf)
            ->where('jenjang', $jenjang)
            ->get();

        $kbDiujiTotal = $v->uji_pertama + $v->uji_reguler + $v->numpang_uji_masuk + $v->mutasi_masuk;

        $totalWpv = 0;

        foreach ($butirKegiatan as $butir) {
            // Baris dengan sumber_volume NULL dilewati (kontribusi 0) -- properti
            // per-baris hasil verifikasi RF-1B, BUKAN per-sub_unsur.
            if ($butir->sumber_volume === null) {
                continue;
            }

            $volume = match ($butir->sumber_volume) {
                'kb_diuji_total' => $kbDiujiTotal,
                'uji_pertama' => $v->uji_pertama,
                'uji_reguler' => $v->uji_reguler,
                'bbm_bensin' => $v->bbm_bensin,
                'bbm_solar' => $v->bbm_solar,
                // Nilai literal ASLI per baris (bukan hardcode 240) -- ditemukan
                // ada baris dengan konstanta berbeda (10) saat validasi RF-1B.
                'konstanta_hari_kerja' => (float) $butir->volume_konstanta,
                default => 0,
            };

            $wpv = ((float) $butir->waktu_menit / 60) * $volume;
            $totalWpv += $wpv;
        }

        $kebutuhanRaw = $totalWpv / 1250;
        $kebutuhanBulat = (int) ceil($kebutuhanRaw); // ROUNDUP semua jenjang -- lihat catatan di atas

        return [
            'total_wpv' => round($totalWpv, 4),
            'kebutuhan_raw' => round($kebutuhanRaw, 4),
            'kebutuhan_bulat' => $kebutuhanBulat,
        ];
    }

    /**
     * Simpan data pegawai yang diupload Dishub -- LANGSUNG masuk ke tabel utama
     * Pegawai JFT (sesuai keputusan produk), plus dicatat di
     * rekomendasi_formasi_pegawai_existing untuk audit trail usulan ini.
     *
     * sumber_daya_manusia TIDAK punya kolom "jenjang" langsung -- jenjang
     * pegawai selalu ditelusuri lewat formasi_jabatan_id. Kalau unit kerja ini
     * sudah punya formasi_jabatan utk PKB+jenjang terkait, pegawai ditautkan
     * ke situ; kalau belum ada (Dishub baru mulai tertib administrasi formasi),
     * pegawai tetap disimpan dg unit_kerja_id langsung + status_formasi
     * 'di_luar_formasi', TANPA mengarang record formasi_jabatan baru.
     *
     * @return array<string,int> bezetting per jenjang, dihitung dari input mentah
     *   (bukan re-query SDM) supaya akurat terlepas dari berhasil/tidaknya link formasi.
     */
    private function simpanPegawaiDishub(array $pegawaiList, RekomendasiFormasiUsulan $usulan, UnitKerja $unitKerja): array
    {
        $bezettingPerJenjang = array_fill_keys(self::JENJANG_LIST, 0);

        foreach ($pegawaiList as $p) {
            if (empty($p['nama']) || empty($p['jenjang'])) {
                continue;
            }

            $formasiJabatanId = $this->cariFormasiJabatanPkb($unitKerja->id, $p['jenjang']);

            $sdm = Sdmmodels::create([
                'nama_lengkap' => $p['nama'],
                'nip' => $p['nip'] ?? null,
                'unit_kerja_id' => $unitKerja->id,
                'formasi_jabatan_id' => $formasiJabatanId,
                'status_formasi' => $formasiJabatanId ? 'terpenuhi' : 'di_luar_formasi',
                'aktif' => true,
            ]);

            RekomendasiFormasiPegawaiExisting::create([
                'usulan_id' => $usulan->id,
                'sdm_id' => $sdm->id,
                'nama' => $p['nama'],
                'nip' => $p['nip'] ?? null,
                'jenjang' => $p['jenjang'],
            ]);

            $bezettingPerJenjang[$p['jenjang']]++;
        }

        return $bezettingPerJenjang;
    }

    /**
     * Bezetting Dishub dari data yang SUDAH tersimpan (dipakai saat update(),
     * bukan simpan baru) -- baca dari rekomendasi_formasi_pegawai_existing,
     * bukan dari sumber_daya_manusia (supaya tidak tergantung berhasil/
     * tidaknya link formasi_jabatan_id).
     */
    private function hitungBezettingDariRfPegawai(RekomendasiFormasiUsulan $usulan): array
    {
        $bezettingPerJenjang = array_fill_keys(self::JENJANG_LIST, 0);

        foreach ($usulan->pegawaiExisting()->get() as $p) {
            if (isset($bezettingPerJenjang[$p->jenjang])) {
                $bezettingPerJenjang[$p->jenjang]++;
            }
        }

        return $bezettingPerJenjang;
    }

    /**
     * Bezetting Kemenhub: dihitung otomatis dari data Pegawai JFT existing yang
     * SUDAH tertaut ke formasi_jabatan JF ini di unit kerja tsb -- bukan dari
     * kolom "jenjang" langsung di sumber_daya_manusia (kolom itu tidak ada;
     * jenjang selalu ditelusuri lewat formasi_jabatan.jenjang_id ->
     * jenjang_jabatan.nama_jenjang, yang formatnya "{Nama JF} {Jenjang}",
     * misal "Penguji Kendaraan Bermotor Pemula").
     */
    private function hitungBezettingKemenhub(UnitKerja $unitKerja, string $kodeJf): array
    {
        $namaJf = self::JF_TERSEDIA[$kodeJf] ?? null;
        $namaFormasi = 'Penguji Kendaraan Bermotor'; // satu-satunya JF dg rumus tersedia saat ini (PKB)

        $bezettingPerJenjang = array_fill_keys(self::JENJANG_LIST, 0);

        foreach (self::JENJANG_LIST as $jenjang) {
            $formasiIds = Formasijabatan::where('unit_kerja_id', $unitKerja->id)
                ->where('nama_formasi', $namaFormasi)
                ->whereHas('jenjang', fn($q) => $q->where('nama_jenjang', $namaFormasi . ' ' . ucfirst($jenjang)))
                ->pluck('id');

            if ($formasiIds->isEmpty()) {
                continue;
            }

            $bezettingPerJenjang[$jenjang] = Sdmmodels::where('aktif', true)
                ->whereIn('formasi_jabatan_id', $formasiIds)
                ->count();
        }

        return $bezettingPerJenjang;
    }

    /**
     * Cari formasi_jabatan PKB milik unit kerja utk jenjang tertentu (kalau ada).
     * TIDAK membuat record baru -- kalau belum ada, pegawai disimpan tanpa
     * tautan formasi (status_formasi = di_luar_formasi).
     */
    private function cariFormasiJabatanPkb(int $unitKerjaId, string $jenjang): ?int
    {
        $namaFormasi = 'Penguji Kendaraan Bermotor';

        return Formasijabatan::where('unit_kerja_id', $unitKerjaId)
            ->where('nama_formasi', $namaFormasi)
            ->whereHas('jenjang', fn($q) => $q->where('nama_jenjang', $namaFormasi . ' ' . ucfirst($jenjang)))
            ->value('id');
    }

    private function authorizeAkses(RekomendasiFormasiUsulan $usulan): void
    {
        $user = Auth::user();

        if ($user->hasRole('admin_unit') && $usulan->unit_kerja_id != $user->unit_kerja_id) {
            abort(403, 'Anda tidak berwenang mengakses usulan unit kerja lain.');
        }
    }
}
