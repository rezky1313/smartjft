<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengangkatanPermohonan;
use App\Models\PengangkatanKandidat;
use App\Models\PengangkatanSurat;
use App\Models\Rumahsakit;
use Barryvdh\DomPDF\Facade\Pdf;

class PengangkatanController extends Controller
{
    /**
     * Daftar permohonan pengangkatan.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $q = PengangkatanPermohonan::with(['unitKerja', 'pengaju'])
            ->withCount([
                'kandidat',
                'kandidat as kandidat_direkomendasikan_count' => fn($q) => $q->where('status_kandidat', 'direkomendasikan'),
            ]);

        // Admin unit hanya lihat miliknya
        if ($user->hasRole('admin_unit')) {
            $q->where('unit_kerja_id', $user->unit_kerja_id);
        }

        // Filter
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('unit_kerja_id')) {
            $q->where('unit_kerja_id', $request->unit_kerja_id);
        }
        if ($request->filled('tahun')) {
            $q->whereYear('tanggal_permohonan', $request->tahun);
        }

        $permohonan = $q->orderByDesc('created_at')->get();

        // Statistik
        $baseStats = PengangkatanPermohonan::query();
        if ($user->hasRole('admin_unit')) {
            $baseStats->where('unit_kerja_id', $user->unit_kerja_id);
        }
        $stats = [
            'total'    => (clone $baseStats)->count(),
            'diajukan' => (clone $baseStats)->where('status', 'diajukan')->count(),
            'disetujui'=> (clone $baseStats)->where('status', 'disetujui')->count(),
            'selesai'  => (clone $baseStats)->where('status', 'selesai')->count(),
        ];

        $unitKerjaList = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        return view('pengangkatan.index', compact('permohonan', 'stats', 'unitKerjaList'));
    }

    /**
     * Form buat permohonan baru.
     */
    public function create()
    {
        $user = auth()->user();
        $unitKerja = $user->hasRole('admin_unit')
            ? Rumahsakit::where('no_rs', $user->unit_kerja_id)->first()
            : null;

        $unitKerjaList = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        return view('pengangkatan.create', compact('unitKerja', 'unitKerjaList'));
    }

    /**
     * Simpan permohonan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit_kerja_id'          => 'required|string',
            'tanggal_permohonan'     => 'required|date',
            'file_surat_permohonan'  => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file_surat_permohonan')) {
            $filePath = $request->file('file_surat_permohonan')->store('pengangkatan/surat-permohonan', 'public');
        }

        $permohonan = PengangkatanPermohonan::create([
            'kode_permohonan'       => PengangkatanPermohonan::generateKode(),
            'unit_kerja_id'         => $request->unit_kerja_id,
            'tanggal_permohonan'    => $request->tanggal_permohonan,
            'file_surat_permohonan' => $filePath,
            'status'                => 'draft',
            'diajukan_oleh'         => auth()->id(),
        ]);

        // Jika klik "Simpan + Ajukan"
        if ($request->input('aksi') === 'ajukan') {
            $permohonan->update(['status' => 'diajukan']);
            return redirect()->route('pengangkatan.show', $permohonan->id)
                ->with('success', 'Permohonan berhasil dibuat dan diajukan.');
        }

        return redirect()->route('pengangkatan.show', $permohonan->id)
            ->with('success', 'Permohonan berhasil disimpan sebagai draft.');
    }

    /**
     * Detail permohonan + daftar kandidat.
     */
    public function show($id)
    {
        $permohonan = PengangkatanPermohonan::with([
            'unitKerja', 'pengaju', 'pemroses', 'surat',
            'kandidat' => fn($q) => $q->orderBy('jabatan_tujuan_id')->orderBy('ranking'),
            'kandidat.pegawai',
            'kandidat.jabatanTujuan.jenjang',
        ])->findOrFail($id);

        // Group kandidat per jabatan tujuan
        $kandidatPerJabatan = $permohonan->kandidat->groupBy('jabatan_tujuan_id');

        return view('pengangkatan.show', compact('permohonan', 'kandidatPerJabatan'));
    }

    /**
     * Form edit (hanya jika draft).
     */
    public function edit($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403, 'Hanya permohonan draft yang bisa diedit.');

        $unitKerjaList = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        return view('pengangkatan.create', compact('permohonan', 'unitKerjaList'));
    }

    /**
     * Update permohonan.
     */
    public function update(Request $request, $id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403);

        $request->validate([
            'unit_kerja_id'          => 'required|string',
            'tanggal_permohonan'     => 'required|date',
            'file_surat_permohonan'  => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $data = $request->only(['unit_kerja_id', 'tanggal_permohonan']);

        if ($request->hasFile('file_surat_permohonan')) {
            $data['file_surat_permohonan'] = $request->file('file_surat_permohonan')
                ->store('pengangkatan/surat-permohonan', 'public');
        }

        $permohonan->update($data);

        return redirect()->route('pengangkatan.show', $permohonan->id)
            ->with('success', 'Permohonan berhasil diperbarui.');
    }

    /**
     * Hapus permohonan (hanya draft).
     */
    public function destroy($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403);

        $permohonan->delete();

        return redirect()->route('pengangkatan.index')
            ->with('success', 'Permohonan berhasil dihapus.');
    }

    /**
     * Admin unit ajukan permohonan: draft → diajukan.
     */
    public function ajukan($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403, 'Hanya draft yang bisa diajukan.');

        $permohonan->update(['status' => 'diajukan']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan berhasil diajukan ke Pusbin.');
    }

    /**
     * Admin Pusbin mulai proses: diajukan → diproses + generate kandidat.
     */
    public function proses($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'diajukan', 403);

        $permohonan->update([
            'status'       => 'diproses',
            'diproses_oleh'=> auth()->id(),
        ]);

        $permohonan->generateKandidat();

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan diproses. Ranking kandidat berhasil digenerate.');
    }

    /**
     * Admin Pusbin setujui: diproses → disetujui + generate surat.
     */
    public function setujui($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'diproses', 403);

        $permohonan->update([
            'status'            => 'disetujui',
            'tanggal_disetujui' => now()->toDateString(),
        ]);

        // Buat record surat jika belum ada
        if (!$permohonan->surat) {
            PengangkatanSurat::create([
                'pengangkatan_permohonan_id' => $permohonan->id,
                'tanggal_surat'              => now()->toDateString(),
            ]);
        }

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan disetujui. Surat rekomendasi siap di-generate.');
    }

    /**
     * Admin Pusbin tolak: → ditolak.
     */
    public function tolak(Request $request, $id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless(in_array($permohonan->status, ['diajukan', 'diproses']), 403);

        $request->validate(['catatan_pusbin' => 'required|string|max:1000']);

        $permohonan->update([
            'status'        => 'ditolak',
            'catatan_pusbin' => $request->catatan_pusbin,
        ]);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan ditolak.');
    }

    /**
     * Konfirmasi surat sudah ditandatangani: disetujui → selesai.
     */
    public function konfirmasiTtd($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'disetujui', 403);

        // Tandai surat sebagai sudah TTD
        if ($permohonan->surat) {
            $permohonan->surat->update(['ditandatangani' => true]);
        }

        // Jalankan proses selesaikan (update formasi pegawai)
        $permohonan->selesaikan();

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Surat dikonfirmasi dan pengangkatan diselesaikan. Data formasi pegawai telah diperbarui.');
    }

    /**
     * Generate PDF surat rekomendasi.
     */
    public function generateSurat($id)
    {
        $permohonan = PengangkatanPermohonan::with([
            'unitKerja', 'surat',
            'kandidat' => fn($q) => $q->where('status_kandidat', 'direkomendasikan')->orderBy('ranking'),
            'kandidat.pegawai',
            'kandidat.jabatanTujuan.jenjang',
        ])->findOrFail($id);

        $kandidatDirekomendasikan = $permohonan->kandidat;

        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'chroot'               => public_path(),
        ])
        ->setPaper('a4', 'portrait')
        ->loadView('pengangkatan.pdf.surat_rekomendasi', compact('permohonan', 'kandidatDirekomendasikan'));

        $filename = 'surat_rekomendasi_' . str_replace('/', '-', $permohonan->kode_permohonan) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * AJAX: Update status kandidat oleh admin Pusbin.
     */
    public function updateKandidat(Request $request, $id)
    {
        $request->validate([
            'kandidat_id'     => 'required|exists:pengangkatan_kandidat,id',
            'status_kandidat' => 'required|in:direkomendasikan,antrian,ditolak_pusbin',
            'catatan'         => 'nullable|string|max:500',
        ]);

        $kandidat = PengangkatanKandidat::where('pengangkatan_permohonan_id', $id)
            ->findOrFail($request->kandidat_id);

        $kandidat->update([
            'status_kandidat' => $request->status_kandidat,
            'catatan'         => $request->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status kandidat berhasil diperbarui.',
            'badge'   => $kandidat->badge_status_kandidat,
            'label'   => $kandidat->label_status_kandidat,
        ]);
    }
}
