<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UjikomJadwal;
use App\Models\UjikomPersyaratan;
use App\Models\UjikomPendaftaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UjikomJadwalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'super_admin'])) {
            $query = UjikomJadwal::with('pembuat');

            if (request('status')) {
                $query->where('status', request('status'));
            }

            $jadwals = $query->orderBy('tanggal_mulai', 'desc')->paginate(15);
            $viewMode = 'admin';
        } else {
            $jadwals = UjikomJadwal::where('status', 'published')
                ->orderBy('tanggal_mulai', 'desc')
                ->paginate(9);
            $viewMode = 'publik';
        }

        return view('ujikom.jadwal.index', compact('jadwals', 'viewMode'));
    }

    public function create()
    {
        return view('ujikom.jadwal.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'                              => 'required|string|max:255',
            'deskripsi'                          => 'nullable|string',
            'tanggal_mulai'                      => 'required|date',
            'tanggal_selesai'                    => 'required|date|after_or_equal:tanggal_mulai',
            'tempat'                             => 'required|string|max:255',
            'kuota'                              => 'required|integer|min:1',
            'persyaratan'                        => 'nullable|array',
            'persyaratan.*.nama_syarat'          => 'required_with:persyaratan|string|max:255',
            'persyaratan.*.keterangan'           => 'nullable|string',
            'persyaratan.*.urutan'               => 'nullable|integer|min:1',
            'persyaratan.*.file_contoh'          => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:5120',
        ]);

        $status = $request->has('publish') ? 'published' : 'draft';

        $jadwal = UjikomJadwal::create([
            'judul'           => $request->judul,
            'deskripsi'       => $request->deskripsi,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'tempat'          => $request->tempat,
            'kuota'           => $request->kuota,
            'status'          => $status,
            'dibuat_oleh'     => Auth::id(),
        ]);

        $this->simpanPersyaratan($request, $jadwal->id);

        $msg = $status === 'published'
            ? 'Jadwal berhasil disimpan dan dipublikasikan.'
            : 'Jadwal berhasil disimpan sebagai draft.';

        return redirect()->route('ujikom.jadwal.index')->with('success', $msg);
    }

    public function show($id)
    {
        $jadwal = UjikomJadwal::with(['persyaratan', 'pembuat'])->findOrFail($id);

        if (!Auth::user()->hasRole(['admin', 'super_admin']) && $jadwal->status !== 'published') {
            abort(403, 'Jadwal ini belum dipublikasikan.');
        }

        // Peserta dari pendaftaran yang sudah selesai/diverifikasi pusbin
        $pendaftaranList = UjikomPendaftaran::with(['peserta.pegawai', 'unitKerja'])
            ->where('ujikom_jadwal_id', $id)
            ->whereIn('status', ['diverifikasi_pusbin', 'selesai'])
            ->get();

        $totalPeserta = $pendaftaranList->sum(fn($p) => $p->peserta->count());

        return view('ujikom.jadwal.show', compact('jadwal', 'pendaftaranList', 'totalPeserta'));
    }

    public function edit($id)
    {
        $jadwal = UjikomJadwal::with('persyaratan')->findOrFail($id);

        if ($jadwal->status !== 'draft') {
            return redirect()->route('ujikom.jadwal.show', $id)
                ->with('error', 'Hanya jadwal berstatus Draft yang dapat diedit.');
        }

        return view('ujikom.jadwal.edit', compact('jadwal'));
    }

    public function update(Request $request, $id)
    {
        $jadwal = UjikomJadwal::with('persyaratan')->findOrFail($id);

        if ($jadwal->status !== 'draft') {
            return redirect()->route('ujikom.jadwal.show', $id)
                ->with('error', 'Hanya jadwal berstatus Draft yang dapat diubah.');
        }

        $request->validate([
            'judul'                              => 'required|string|max:255',
            'deskripsi'                          => 'nullable|string',
            'tanggal_mulai'                      => 'required|date',
            'tanggal_selesai'                    => 'required|date|after_or_equal:tanggal_mulai',
            'tempat'                             => 'required|string|max:255',
            'kuota'                              => 'required|integer|min:1',
            'persyaratan'                        => 'nullable|array',
            'persyaratan.*.nama_syarat'          => 'required_with:persyaratan|string|max:255',
            'persyaratan.*.keterangan'           => 'nullable|string',
            'persyaratan.*.urutan'               => 'nullable|integer|min:1',
            'persyaratan.*.file_contoh'          => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:5120',
        ]);

        $status = $request->has('publish') ? 'published' : 'draft';

        $jadwal->update([
            'judul'           => $request->judul,
            'deskripsi'       => $request->deskripsi,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'tempat'          => $request->tempat,
            'kuota'           => $request->kuota,
            'status'          => $status,
        ]);

        // Hapus persyaratan lama beserta file-nya, lalu buat ulang
        foreach ($jadwal->persyaratan as $p) {
            if ($p->file_contoh) {
                Storage::disk('public')->delete($p->file_contoh);
            }
            $p->delete();
        }

        $this->simpanPersyaratan($request, $jadwal->id);

        $msg = $status === 'published'
            ? 'Jadwal berhasil diperbarui dan dipublikasikan.'
            : 'Jadwal berhasil diperbarui.';

        return redirect()->route('ujikom.jadwal.show', $jadwal->id)->with('success', $msg);
    }

    public function destroy($id)
    {
        $jadwal = UjikomJadwal::with('persyaratan')->findOrFail($id);

        if ($jadwal->status !== 'draft') {
            return redirect()->route('ujikom.jadwal.index')
                ->with('error', 'Hanya jadwal berstatus Draft yang dapat dihapus.');
        }

        foreach ($jadwal->persyaratan as $p) {
            if ($p->file_contoh) {
                Storage::disk('public')->delete($p->file_contoh);
            }
            $p->delete();
        }

        $jadwal->delete();
        return redirect()->route('ujikom.jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function publish($id)
    {
        $jadwal = UjikomJadwal::findOrFail($id);

        if ($jadwal->status !== 'draft') {
            return redirect()->route('ujikom.jadwal.show', $id)
                ->with('error', 'Hanya jadwal berstatus Draft yang dapat dipublikasikan.');
        }

        $jadwal->update(['status' => 'published']);
        return redirect()->route('ujikom.jadwal.show', $id)
            ->with('success', 'Jadwal berhasil dipublikasikan.');
    }

    public function selesaikan($id)
    {
        $jadwal = UjikomJadwal::findOrFail($id);

        if ($jadwal->status !== 'published') {
            return redirect()->route('ujikom.jadwal.show', $id)
                ->with('error', 'Hanya jadwal berstatus Dipublikasikan yang dapat diselesaikan.');
        }

        $jadwal->update(['status' => 'selesai']);
        return redirect()->route('ujikom.jadwal.show', $id)
            ->with('success', 'Jadwal berhasil diselesaikan.');
    }

    // ─── Private Helper ───────────────────────────────────────────────────────

    private function simpanPersyaratan(Request $request, int $jadwalId): void
    {
        if (!$request->has('persyaratan')) {
            return;
        }

        foreach ($request->persyaratan as $index => $syarat) {
            if (empty($syarat['nama_syarat'])) {
                continue;
            }

            $filePath = null;
            if ($request->hasFile("persyaratan.{$index}.file_contoh")) {
                $filePath = $request->file("persyaratan.{$index}.file_contoh")
                    ->store('ujikom/persyaratan', 'public');
            }

            UjikomPersyaratan::create([
                'ujikom_jadwal_id' => $jadwalId,
                'nama_syarat'      => $syarat['nama_syarat'],
                'keterangan'       => $syarat['keterangan'] ?? null,
                'file_contoh'      => $filePath,
                'urutan'           => $syarat['urutan'] ?? ($index + 1),
            ]);
        }
    }
}
