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
            'durasi_menit'       => 'required|integer|min:5|max:360',
            'passing_grade'      => 'required|integer|min:0|max:100',
            'jumlah_soal'        => 'required|integer|min:1',
            'mode_pemilihan'     => 'required|in:acak_otomatis,manual',
            'deskripsi'          => 'nullable|string',
            'simpan_sebagai'     => 'required|in:draft,aktif',
            // Validasi mode acak
            'kategori_acak'                  => 'required_if:mode_pemilihan,acak_otomatis|array',
            'kategori_acak.*.jenis_soal'     => 'required_if:mode_pemilihan,acak_otomatis|in:umum,spesifik',
            'kategori_acak.*.jumlah_soal'    => 'required_if:mode_pemilihan,acak_otomatis|integer|min:1',
            // Validasi mode manual
            'soal_terpilih'   => 'required_if:mode_pemilihan,manual|array|min:1',
            'soal_terpilih.*' => 'exists:bank_soal,id',
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

        DB::transaction(function () use ($request) {
            $paket = PaketUjian::create([
                'nama'             => $request->nama,
                'ujikom_jadwal_id' => $request->ujikom_jadwal_id,
                'deskripsi'        => $request->deskripsi,
                'durasi_menit'     => $request->durasi_menit,
                'passing_grade'    => $request->passing_grade,
                'jumlah_soal'      => $request->jumlah_soal,
                'mode_pemilihan'   => $request->mode_pemilihan,
                'status'           => $request->simpan_sebagai,
                'acak_soal'        => $request->boolean('acak_soal', true),
                'acak_pilihan'     => $request->boolean('acak_pilihan', true),
                'dibuat_oleh'      => Auth::id(),
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
            } else {
                foreach ($request->soal_terpilih as $urutan => $soalId) {
                    PaketUjianSoal::create([
                        'paket_ujian_id' => $paket->id,
                        'bank_soal_id'   => $soalId,
                        'urutan'         => $urutan + 1,
                    ]);
                }
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
        ])->findOrFail($id);

        $ketersediaan = $paket->cekKetersediaanSoal();

        // Statistik soal per tingkat (mode manual)
        $statTingkat = [];
        if ($paket->mode_pemilihan === 'manual') {
            $statTingkat = $paket->bankSoal()
                ->select('tingkat_kesulitan', DB::raw('count(*) as total'))
                ->groupBy('tingkat_kesulitan')
                ->pluck('total', 'tingkat_kesulitan')
                ->toArray();
        }

        return view('paket_ujian.show', compact('paket', 'ketersediaan', 'statTingkat'));
    }

    public function edit($id)
    {
        $paket     = PaketUjian::with(['kategoriAcak', 'soal.bankSoal'])->findOrFail($id);
        $jadwals   = UjikomJadwal::where('status', 'published')->orderByDesc('tanggal_mulai')->get(['id', 'judul']);
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();

        return view('paket_ujian.edit', compact('paket', 'jadwals', 'kategoris'));
    }

    public function update(Request $request, $id)
    {
        $paket = PaketUjian::findOrFail($id);

        $request->validate([
            'nama'             => 'required|string|max:255',
            'ujikom_jadwal_id' => 'nullable|exists:ujikom_jadwal,id',
            'durasi_menit'     => 'required|integer|min:5|max:360',
            'passing_grade'    => 'required|integer|min:0|max:100',
            'jumlah_soal'      => 'required|integer|min:1',
            'mode_pemilihan'   => 'required|in:acak_otomatis,manual',
            'deskripsi'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $paket) {
            $paket->update([
                'nama'             => $request->nama,
                'ujikom_jadwal_id' => $request->ujikom_jadwal_id,
                'deskripsi'        => $request->deskripsi,
                'durasi_menit'     => $request->durasi_menit,
                'passing_grade'    => $request->passing_grade,
                'jumlah_soal'      => $request->jumlah_soal,
                'mode_pemilihan'   => $request->mode_pemilihan,
                'acak_soal'        => $request->boolean('acak_soal', true),
                'acak_pilihan'     => $request->boolean('acak_pilihan', true),
            ]);

            // Hapus konfigurasi lama dan simpan yang baru
            $paket->kategoriAcak()->delete();
            $paket->soal()->delete();

            if ($request->mode_pemilihan === 'acak_otomatis') {
                foreach (($request->kategori_acak ?? []) as $cfg) {
                    PaketUjianKategoriAcak::create([
                        'paket_ujian_id'   => $paket->id,
                        'soal_kategori_id' => ($cfg['jenis_soal'] === 'spesifik') ? ($cfg['soal_kategori_id'] ?? null) : null,
                        'jenis_soal'       => $cfg['jenis_soal'],
                        'jumlah_soal'      => $cfg['jumlah_soal'],
                    ]);
                }
            } else {
                foreach (($request->soal_terpilih ?? []) as $urutan => $soalId) {
                    PaketUjianSoal::create([
                        'paket_ujian_id' => $paket->id,
                        'bank_soal_id'   => $soalId,
                        'urutan'         => $urutan + 1,
                    ]);
                }
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
                'tingkat'          => $s->label_tingkat,
                'badge_tingkat'    => $s->badge_tingkat,
                'taksonomi'        => $s->label_taksonomi,
            ]),
        ]);
    }

    public function getSoalByKategori(Request $request)
    {
        $query = BankSoal::aktif()->with('kategori')->orderBy('id');

        if ($request->filled('kategori_id')) {
            $query->where('soal_kategori_id', $request->kategori_id);
        }
        if ($request->filled('tingkat')) {
            $query->where('tingkat_kesulitan', $request->tingkat);
        }
        if ($request->filled('taksonomi')) {
            $query->where('taksonomi_bloom', $request->taksonomi);
        }
        if ($request->filled('q')) {
            $query->where('pertanyaan', 'like', '%' . $request->q . '%');
        }

        $soals = $query->get(['id', 'pertanyaan', 'tingkat_kesulitan', 'taksonomi_bloom', 'soal_kategori_id', 'jenis']);

        return response()->json($soals->map(fn($s) => [
            'id'         => $s->id,
            'pertanyaan' => mb_substr($s->pertanyaan, 0, 120),
            'kategori'   => $s->kategori?->nama ?? 'Umum',
            'tingkat'    => $s->label_tingkat,
            'badge'      => $s->badge_tingkat,
            'taksonomi'  => explode('_', $s->taksonomi_bloom)[0],
            'jenis'      => $s->jenis,
        ]));
    }

    // Property sementara untuk store()
    private PaketUjian $currentPaket;
}
