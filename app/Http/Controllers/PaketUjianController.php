<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PaketUjian;
use App\Models\PaketUjianSoal;
use App\Models\PaketUjianKategoriAcak;
use App\Models\BankSoal;
use App\Models\SoalKategori;
use App\Models\UjikomJadwal;

class PaketUjianController extends Controller
{
    public function index(Request $request)
    {
        $query = PaketUjian::with(['jadwal', 'pembuat'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('jadwal_id')) {
            $query->where('ujikom_jadwal_id', $request->jadwal_id);
        }
        if ($request->filled('mode')) {
            $query->where('mode_pemilihan', $request->mode);
        }

        $pakets    = $query->paginate(15)->withQueryString();
        $jadwals   = UjikomJadwal::orderByDesc('tanggal_mulai')->get(['id', 'judul']);
        $statistik = [
            'total'    => PaketUjian::count(),
            'aktif'    => PaketUjian::where('status', 'aktif')->count(),
            'draft'    => PaketUjian::where('status', 'draft')->count(),
            'nonaktif' => PaketUjian::where('status', 'nonaktif')->count(),
        ];

        return view('paket_ujian.index', compact('pakets', 'jadwals', 'statistik'));
    }

    public function create()
    {
        $jadwals   = UjikomJadwal::where('status', 'published')->orderByDesc('tanggal_mulai')->get(['id', 'judul']);
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();
        return view('paket_ujian.create', compact('jadwals', 'kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'               => 'required|string|max:255',
            'ujikom_jadwal_id'   => 'nullable|exists:ujikom_jadwal,id',
            'durasi_menit'       => 'required_unless:mode_pemilihan,sesi_taksonomi|integer|min:5|max:360',
            'passing_grade'      => 'required|integer|min:0|max:100',
            'jumlah_soal'        => 'required_unless:mode_pemilihan,sesi_taksonomi|integer|min:1',
            'mode_pemilihan'     => 'required|in:acak_otomatis,manual,sesi_taksonomi',
            'deskripsi'          => 'nullable|string',
            'simpan_sebagai'     => 'required|in:draft,aktif',
            // Validasi mode acak
            'kategori_acak'                  => 'required_if:mode_pemilihan,acak_otomatis|array',
            'kategori_acak.*.jenis_soal'     => 'required_if:mode_pemilihan,acak_otomatis|in:umum,spesifik',
            'kategori_acak.*.jumlah_soal'    => 'required_if:mode_pemilihan,acak_otomatis|integer|min:1',
            // Validasi mode manual
            'soal_terpilih'   => 'required_if:mode_pemilihan,manual|array|min:1',
            'soal_terpilih.*' => 'exists:bank_soal,id',
            // Validasi mode sesi_taksonomi — Sesi Teknis (semua wajib kalau jumlah_soal_teknis diisi)
            'durasi_menit_teknis'      => 'nullable|required_with:jumlah_soal_teknis|integer|min:5|max:360',
            'jumlah_soal_teknis'       => 'nullable|integer|min:1',
            'taksonomi_maks_teknis'    => 'nullable|required_with:jumlah_soal_teknis|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'soal_kategori_id_teknis'  => 'nullable|required_with:jumlah_soal_teknis|exists:soal_kategori,id',
            // Validasi mode sesi_taksonomi — Sesi Mansoskul
            'durasi_menit_mansoskul'   => 'nullable|required_with:jumlah_soal_mansoskul|integer|min:5|max:360',
            'jumlah_soal_mansoskul'    => 'nullable|integer|min:1',
            'taksonomi_maks_mansoskul' => 'nullable|required_with:jumlah_soal_mansoskul|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'matra_mansoskul'          => 'nullable|required_with:jumlah_soal_mansoskul|in:darat,laut,udara,asdp,perkeretaapian',
        ]);

        if ($request->simpan_sebagai === 'aktif' && $request->ujikom_jadwal_id) {
            $paketAktifLain = PaketUjian::where('ujikom_jadwal_id', $request->ujikom_jadwal_id)
                ->where('status', 'aktif')
                ->first();

            if ($paketAktifLain) {
                return back()->withInput()->withErrors([
                    'ujikom_jadwal_id' => "Jadwal ini sudah punya paket aktif: \"{$paketAktifLain->nama}\". Nonaktifkan dulu sebelum menambah paket aktif baru.",
                ]);
            }
        }

        if ($request->mode_pemilihan === 'sesi_taksonomi' && !$request->jumlah_soal_teknis && !$request->jumlah_soal_mansoskul) {
            return back()->withInput()->withErrors([
                'mode_pemilihan' => 'Isi konfigurasi minimal satu sesi (Teknis atau Mansoskul).',
            ]);
        }

        DB::transaction(function () use ($request) {
            $isSesiTaksonomi = $request->mode_pemilihan === 'sesi_taksonomi';

            $paket = PaketUjian::create([
                'nama'                     => $request->nama,
                'ujikom_jadwal_id'         => $request->ujikom_jadwal_id,
                'deskripsi'                => $request->deskripsi,
                'durasi_menit'             => $isSesiTaksonomi
                    ? ((int) $request->durasi_menit_teknis + (int) $request->durasi_menit_mansoskul)
                    : $request->durasi_menit,
                'passing_grade'            => $request->passing_grade,
                'jumlah_soal'              => $isSesiTaksonomi
                    ? ((int) $request->jumlah_soal_teknis + (int) $request->jumlah_soal_mansoskul)
                    : $request->jumlah_soal,
                'mode_pemilihan'           => $request->mode_pemilihan,
                'status'                   => $request->simpan_sebagai,
                'acak_soal'                => $request->boolean('acak_soal', true),
                'acak_pilihan'             => $request->boolean('acak_pilihan', true),
                'dibuat_oleh'              => Auth::id(),
                'durasi_menit_teknis'      => $request->durasi_menit_teknis,
                'jumlah_soal_teknis'       => $request->jumlah_soal_teknis,
                'taksonomi_maks_teknis'    => $request->taksonomi_maks_teknis,
                'soal_kategori_id_teknis'  => $request->soal_kategori_id_teknis,
                'durasi_menit_mansoskul'   => $request->durasi_menit_mansoskul,
                'jumlah_soal_mansoskul'    => $request->jumlah_soal_mansoskul,
                'taksonomi_maks_mansoskul' => $request->taksonomi_maks_mansoskul,
                'matra_mansoskul'          => $request->matra_mansoskul,
            ]);

            if ($request->mode_pemilihan === 'acak_otomatis') {
                foreach ($request->kategori_acak as $cfg) {
                    PaketUjianKategoriAcak::create([
                        'paket_ujian_id'   => $paket->id,
                        'soal_kategori_id' => ($cfg['jenis_soal'] === 'spesifik') ? ($cfg['soal_kategori_id'] ?? null) : null,
                        'jenis_soal'       => $cfg['jenis_soal'],
                        'jumlah_soal'      => $cfg['jumlah_soal'],
                    ]);
                }
            } elseif ($request->mode_pemilihan === 'manual') {
                foreach ($request->soal_terpilih as $urutan => $soalId) {
                    PaketUjianSoal::create([
                        'paket_ujian_id' => $paket->id,
                        'bank_soal_id'   => $soalId,
                        'urutan'         => $urutan + 1,
                    ]);
                }
            } else {
                $paket->generateKomposisiTaksonomi();
            }

            $this->currentPaket = $paket;
        });

        return redirect()->route('paket-ujian.show', $this->currentPaket->id)
            ->with('success', 'Paket ujian berhasil disimpan.');
    }

    public function show($id)
    {
        $paket = PaketUjian::with([
            'jadwal', 'pembuat',
            'kategoriAcak.kategori',
            'bankSoal.pilihan', 'bankSoal.kategori',
            'komposisiTaksonomi', 'kategoriTeknis',
        ])->findOrFail($id);

        $ketersediaan = $paket->cekKetersediaanSoal();

        // Statistik soal per jenis (mode manual)
        $statJenis = [];
        if ($paket->mode_pemilihan === 'manual') {
            $statJenis = $paket->bankSoal()
                ->select('jenis', DB::raw('count(*) as total'))
                ->groupBy('jenis')
                ->pluck('total', 'jenis')
                ->toArray();
        }

        return view('paket_ujian.show', compact('paket', 'ketersediaan', 'statJenis'));
    }

    public function edit($id)
    {
        $paket     = PaketUjian::with(['kategoriAcak', 'soal.bankSoal', 'komposisiTaksonomi'])->findOrFail($id);
        $jadwals   = UjikomJadwal::where('status', 'published')->orderByDesc('tanggal_mulai')->get(['id', 'judul']);
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();

        // Dibentuk di controller (bukan langsung di @json blade) agar tidak memicu
        // bug parsing directive Blade saat array literal multi-baris berisi pemanggilan fungsi.
        $soalTerpilihPreload = $paket->bankSoal->map(function ($s) {
            return [
                'id'          => $s->id,
                'pertanyaan'  => \Illuminate\Support\Str::limit($s->pertanyaan, 100),
                'label_jenis' => $s->label_jenis,
                'badge'       => $s->jenis === 'mansoskul' ? 'light border' : 'info',
                'taksonomi'   => explode('_', $s->taksonomi_bloom)[0],
            ];
        });

        return view('paket_ujian.edit', compact('paket', 'jadwals', 'kategoris', 'soalTerpilihPreload'));
    }

    public function update(Request $request, $id)
    {
        $paket = PaketUjian::findOrFail($id);

        $request->validate([
            'nama'             => 'required|string|max:255',
            'ujikom_jadwal_id' => 'nullable|exists:ujikom_jadwal,id',
            'durasi_menit'     => 'required_unless:mode_pemilihan,sesi_taksonomi|integer|min:5|max:360',
            'passing_grade'    => 'required|integer|min:0|max:100',
            'jumlah_soal'      => 'required_unless:mode_pemilihan,sesi_taksonomi|integer|min:1',
            'mode_pemilihan'   => 'required|in:acak_otomatis,manual,sesi_taksonomi',
            'deskripsi'        => 'nullable|string',
            // Validasi mode sesi_taksonomi — Sesi Teknis
            'durasi_menit_teknis'      => 'nullable|required_with:jumlah_soal_teknis|integer|min:5|max:360',
            'jumlah_soal_teknis'       => 'nullable|integer|min:1',
            'taksonomi_maks_teknis'    => 'nullable|required_with:jumlah_soal_teknis|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'soal_kategori_id_teknis'  => 'nullable|required_with:jumlah_soal_teknis|exists:soal_kategori,id',
            // Validasi mode sesi_taksonomi — Sesi Mansoskul
            'durasi_menit_mansoskul'   => 'nullable|required_with:jumlah_soal_mansoskul|integer|min:5|max:360',
            'jumlah_soal_mansoskul'    => 'nullable|integer|min:1',
            'taksonomi_maks_mansoskul' => 'nullable|required_with:jumlah_soal_mansoskul|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'matra_mansoskul'          => 'nullable|required_with:jumlah_soal_mansoskul|in:darat,laut,udara,asdp,perkeretaapian',
        ]);

        if ($request->mode_pemilihan === 'sesi_taksonomi' && !$request->jumlah_soal_teknis && !$request->jumlah_soal_mansoskul) {
            return back()->withInput()->withErrors([
                'mode_pemilihan' => 'Isi konfigurasi minimal satu sesi (Teknis atau Mansoskul).',
            ]);
        }

        DB::transaction(function () use ($request, $paket) {
            $isSesiTaksonomi = $request->mode_pemilihan === 'sesi_taksonomi';

            $paket->update([
                'nama'                     => $request->nama,
                'ujikom_jadwal_id'         => $request->ujikom_jadwal_id,
                'deskripsi'                => $request->deskripsi,
                'durasi_menit'             => $isSesiTaksonomi
                    ? ((int) $request->durasi_menit_teknis + (int) $request->durasi_menit_mansoskul)
                    : $request->durasi_menit,
                'passing_grade'            => $request->passing_grade,
                'jumlah_soal'              => $isSesiTaksonomi
                    ? ((int) $request->jumlah_soal_teknis + (int) $request->jumlah_soal_mansoskul)
                    : $request->jumlah_soal,
                'mode_pemilihan'           => $request->mode_pemilihan,
                'acak_soal'                => $request->boolean('acak_soal', true),
                'acak_pilihan'             => $request->boolean('acak_pilihan', true),
                'durasi_menit_teknis'      => $request->durasi_menit_teknis,
                'jumlah_soal_teknis'       => $request->jumlah_soal_teknis,
                'taksonomi_maks_teknis'    => $request->taksonomi_maks_teknis,
                'soal_kategori_id_teknis'  => $request->soal_kategori_id_teknis,
                'durasi_menit_mansoskul'   => $request->durasi_menit_mansoskul,
                'jumlah_soal_mansoskul'    => $request->jumlah_soal_mansoskul,
                'taksonomi_maks_mansoskul' => $request->taksonomi_maks_mansoskul,
                'matra_mansoskul'          => $request->matra_mansoskul,
            ]);

            // Hapus konfigurasi lama dan simpan yang baru
            $paket->kategoriAcak()->delete();
            $paket->soal()->delete();
            $paket->komposisiTaksonomi()->delete();

            if ($request->mode_pemilihan === 'acak_otomatis') {
                foreach (($request->kategori_acak ?? []) as $cfg) {
                    PaketUjianKategoriAcak::create([
                        'paket_ujian_id'   => $paket->id,
                        'soal_kategori_id' => ($cfg['jenis_soal'] === 'spesifik') ? ($cfg['soal_kategori_id'] ?? null) : null,
                        'jenis_soal'       => $cfg['jenis_soal'],
                        'jumlah_soal'      => $cfg['jumlah_soal'],
                    ]);
                }
            } elseif ($request->mode_pemilihan === 'manual') {
                foreach (($request->soal_terpilih ?? []) as $urutan => $soalId) {
                    PaketUjianSoal::create([
                        'paket_ujian_id' => $paket->id,
                        'bank_soal_id'   => $soalId,
                        'urutan'         => $urutan + 1,
                    ]);
                }
            } else {
                $paket->generateKomposisiTaksonomi();
            }
        });

        return redirect()->route('paket-ujian.show', $paket->id)->with('success', 'Paket ujian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $paket = PaketUjian::findOrFail($id);

        if ($paket->status !== 'draft') {
            return back()->with('error', 'Hanya paket berstatus Draft yang dapat dihapus.');
        }

        $paket->delete();
        return redirect()->route('paket-ujian.index')->with('success', 'Paket ujian berhasil dihapus.');
    }

    public function aktifkan($id)
    {
        $paket = PaketUjian::with('kategoriAcak')->findOrFail($id);

        if ($paket->status !== 'draft') {
            return back()->with('error', 'Hanya paket berstatus Draft yang dapat diaktifkan.');
        }

        $cek = $paket->cekKetersediaanSoal();
        if (!$cek['cukup']) {
            return back()->with('error', 'Jumlah soal di bank tidak mencukupi. Tambah soal terlebih dahulu.');
        }

        if ($paket->ujikom_jadwal_id) {
            $paketAktifLain = PaketUjian::where('ujikom_jadwal_id', $paket->ujikom_jadwal_id)
                ->where('status', 'aktif')
                ->where('id', '!=', $id)
                ->first();

            if ($paketAktifLain) {
                return back()->with('error',
                    "Jadwal ujikom ini sudah memiliki paket aktif: \"{$paketAktifLain->nama}\". Nonaktifkan paket tersebut terlebih dahulu sebelum mengaktifkan paket baru."
                );
            }
        }

        $paket->update(['status' => 'aktif']);
        return back()->with('success', 'Paket ujian berhasil diaktifkan.');
    }

    public function nonaktifkan($id)
    {
        $paket = PaketUjian::findOrFail($id);

        if ($paket->status !== 'aktif') {
            return back()->with('error', 'Hanya paket berstatus Aktif yang dapat dinonaktifkan.');
        }

        $paket->update(['status' => 'nonaktif']);
        return back()->with('success', 'Paket ujian berhasil dinonaktifkan.');
    }

    public function previewSoal($id)
    {
        $paket = PaketUjian::with('kategoriAcak.kategori')->findOrFail($id);

        // Generate preview (tanpa menyimpan)
        $soal = $paket->generateSoalUntukPeserta('preview');

        return response()->json([
            'jumlah'   => $soal->count(),
            'soal'     => $soal->map(fn($s) => [
                'id'               => $s->id,
                'pertanyaan'       => mb_substr($s->pertanyaan, 0, 100) . (mb_strlen($s->pertanyaan) > 100 ? '...' : ''),
                'kategori'         => $s->kategori?->nama ?? 'Umum',
                'label_jenis'      => $s->label_jenis,
                'badge_jenis'      => $s->jenis === 'mansoskul' ? 'light border' : 'info',
                'taksonomi'        => $s->label_taksonomi,
            ]),
        ]);
    }

    /**
     * AJAX: preview komposisi taksonomi (mode sesi_taksonomi) — dipanggil realtime dari
     * form create/edit saat admin mengubah taksonomi maksimal / jumlah soal per sesi.
     */
    public function previewKomposisi(Request $request)
    {
        $request->validate([
            'jenis_sesi'       => 'required|in:teknis,mansoskul',
            'taksonomi_maks'   => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'jumlah_soal'      => 'required|integer|min:1',
            'soal_kategori_id' => 'nullable|exists:soal_kategori,id',
            'matra'            => 'nullable|in:darat,laut,udara,asdp,perkeretaapian',
        ]);

        $komposisi = (new PaketUjian())->hitungKomposisiTaksonomi($request->taksonomi_maks, (int) $request->jumlah_soal);

        $labelTaksonomi = [
            'C1_mengingat'    => 'C1 — Mengingat',
            'C2_memahami'     => 'C2 — Memahami',
            'C3_menerapkan'   => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencipta'     => 'C6 — Mencipta',
        ];

        $detail     = [];
        $semuaCukup = true;

        foreach ($komposisi as $taksonomi => $jumlah) {
            if ($jumlah <= 0) continue;

            $query = BankSoal::aktif()->where('taksonomi_bloom', $taksonomi);
            if ($request->jenis_sesi === 'teknis') {
                $query->where('jenis', 'teknis')->where('soal_kategori_id', $request->soal_kategori_id);
            } else {
                $query->where('jenis', 'mansoskul')->where('matra', $request->matra);
            }

            $tersedia = $query->count();
            $cukup    = $tersedia >= $jumlah;
            if (!$cukup) $semuaCukup = false;

            $detail[] = [
                'taksonomi' => $taksonomi,
                'label'     => $labelTaksonomi[$taksonomi],
                'jumlah'    => $jumlah,
                'tersedia'  => $tersedia,
                'cukup'     => $cukup,
            ];
        }

        return response()->json([
            'total'  => array_sum($komposisi),
            'cukup'  => $semuaCukup,
            'detail' => $detail,
        ]);
    }

    public function getSoalByKategori(Request $request)
    {
        $query = BankSoal::aktif()->with('kategori')->orderBy('id');

        if ($request->filled('kategori_id')) {
            $query->where('soal_kategori_id', $request->kategori_id);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('taksonomi')) {
            $query->where('taksonomi_bloom', $request->taksonomi);
        }
        if ($request->filled('q')) {
            $query->where('pertanyaan', 'like', '%' . $request->q . '%');
        }

        $soals = $query->get(['id', 'pertanyaan', 'taksonomi_bloom', 'soal_kategori_id', 'jenis']);

        return response()->json($soals->map(fn($s) => [
            'id'          => $s->id,
            'pertanyaan'  => mb_substr($s->pertanyaan, 0, 120),
            'kategori'    => $s->kategori?->nama ?? 'Umum',
            'jenis'       => $s->jenis,
            'label_jenis' => $s->label_jenis,
            'badge'       => $s->jenis === 'mansoskul' ? 'light border' : 'info',
            'taksonomi'   => explode('_', $s->taksonomi_bloom)[0],
        ]));
    }

    // Property sementara untuk store()
    private PaketUjian $currentPaket;
}
