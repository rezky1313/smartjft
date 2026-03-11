<?php

namespace App\Http\Controllers;

use App\Models\UjikomPermohonan;
use App\Models\UjikomPeserta;
use App\Models\UjikomBeritaAcara;
use App\Models\Sdmmodels;
use App\Models\Rumahsakit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class UjikomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view ujikom');

        $query = UjikomPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta',
        ]);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by unit kerja
        if ($request->has('unit_kerja_id') && $request->unit_kerja_id) {
            $query->where('unit_kerja_id', $request->unit_kerja_id);
        }

        // Filter by tahun
        if ($request->has('tahun') && $request->tahun) {
            $query->whereYear('tanggal_permohonan', $request->tahun);
        }

        $permohonan = $query->latest()->get();
        $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        // Get available years
        $tahuns = UjikomPermohonan::selectRaw('YEAR(tanggal_permohonan) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return view('ujikom.index', compact('permohonan', 'unitKerja', 'tahuns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create ujikom');

        $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        // Load semua pegawai aktif untuk dropdown
        $pegawai = Sdmmodels::where('aktif', true)
            ->with(['formasi.jenjang', 'unitKerja'])
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'formasi_jabatan_id', 'unit_kerja_id']);

        return view('ujikom.create', compact('unitKerja', 'pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create ujikom');

        $validated = $request->validate([
            'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
            'tanggal_permohonan' => 'required|date',
            'file_surat_permohonan' => 'required|file|mimes:pdf|max:2048',
            'peserta' => 'required|array|min:1',
            'peserta.*' => 'required|exists:sumber_daya_manusia,id',
            'ajukan_sekarang' => 'nullable|boolean',
        ]);

        // Generate nomor permohonan
        $nomorPermohonan = UjikomPermohonan::generateNomorPermohonan($validated['tanggal_permohonan']);

        // Upload file surat permohonan
        $filePath = null;
        if ($request->hasFile('file_surat_permohonan')) {
            $file = $request->file('file_surat_permohonan');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('ujikom/surat_permohonan', $fileName, 'public');
        }

        // Create permohonan
        $status = $request->has('ajukan_sekarang') && $request->ajukan_sekarang ? 'diajukan' : 'draft';
        $permohonan = UjikomPermohonan::create([
            'nomor_permohonan' => $nomorPermohonan,
            'unit_kerja_id' => $validated['unit_kerja_id'],
            'file_surat_permohonan' => $filePath,
            'tanggal_permohonan' => $validated['tanggal_permohonan'],
            'status' => $status,
            'created_by' => Auth::id(),
        ]);

        // Create peserta
        foreach ($validated['peserta'] as $pegawaiId) {
            UjikomPeserta::create([
                'ujikom_permohonan_id' => $permohonan->id,
                'pegawai_id' => $pegawaiId,
            ]);
        }

        $message = $status === 'diajukan'
            ? 'Permohonan berhasil dibuat dan diajukan.'
            : 'Permohonan berhasil disimpan sebagai draft.';

        return redirect()->route('ujikom.show', $permohonan->id)
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('view ujikom');

        $permohonan = UjikomPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta.pegawai.formasi.jenjang',
            'peserta.pegawai.unitKerja',
            'beritaAcara',
        ])->findOrFail($id);

        // Get timeline steps
        $statusOrder = ['draft', 'diajukan', 'diverifikasi', 'terjadwal', 'selesai_uji', 'hasil_diinput', 'selesai'];
        $currentStatusIndex = array_search($permohonan->status, $statusOrder);

        return view('ujikom.show', compact('permohonan', 'statusOrder', 'currentStatusIndex'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->authorize('edit ujikom');

        $permohonan = UjikomPermohonan::with(['peserta', 'unitKerja'])->findOrFail($id);

        // Hanya draft yang bisa diedit
        if (!$permohonan->bisaDiedit()) {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa diedit.');
        }

        $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);

        // Load semua pegawai aktif untuk dropdown
        $pegawai = Sdmmodels::where('aktif', true)
            ->with(['formasi.jenjang', 'unitKerja'])
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'formasi_jabatan_id', 'unit_kerja_id']);

        return view('ujikom.edit', compact('permohonan', 'unitKerja', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('edit ujikom');

        $permohonan = UjikomPermohonan::findOrFail($id);

        // Hanya draft yang bisa diedit
        if (!$permohonan->bisaDiedit()) {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa diedit.');
        }

        $validated = $request->validate([
            'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
            'tanggal_permohonan' => 'required|date',
            'file_surat_permohonan' => 'nullable|file|mimes:pdf|max:2048',
            'peserta' => 'required|array|min:1',
            'peserta.*' => 'required|exists:sumber_daya_manusia,id',
        ]);

        // Update file jika ada
        if ($request->hasFile('file_surat_permohonan')) {
            // Delete old file
            if ($permohonan->file_surat_permohonan) {
                Storage::disk('public')->delete($permohonan->file_surat_permohonan);
            }

            $file = $request->file('file_surat_permohonan');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('ujikom/surat_permohonan', $fileName, 'public');
            $validated['file_surat_permohonan'] = $filePath;
        } else {
            $validated['file_surat_permohonan'] = $permohonan->file_surat_permohonan;
        }

        // Regenerate nomor permohonan jika tanggal berubah
        if ($permohonan->tanggal_permohonan != $validated['tanggal_permohonan']) {
            $validated['nomor_permohonan'] = UjikomPermohonan::generateNomorPermohonan($validated['tanggal_permohonan']);
        }

        $permohonan->update([
            'unit_kerja_id' => $validated['unit_kerja_id'],
            'file_surat_permohonan' => $validated['file_surat_permohonan'],
            'tanggal_permohonan' => $validated['tanggal_permohonan'],
            'nomor_permohonan' => $validated['nomor_permohonan'] ?? $permohonan->nomor_permohonan,
        ]);

        // Sync peserta dengan pendekatan yang lebih aman untuk soft delete
        $newPesertaIds = $validated['peserta'];

        // Get ALL existing peserta (including soft-deleted)
        $allExistingPeserta = UjikomPeserta::withTrashed()
            ->where('ujikom_permohonan_id', $permohonan->id)
            ->get();

        foreach ($allExistingPeserta as $existingPeserta) {
            // Jika peserta exist tidak ada di list baru, force delete
            if (!in_array($existingPeserta->pegawai_id, $newPesertaIds)) {
                $existingPeserta->forceDelete();
            }
            // Jika peserta exist ada di list baru dan sedang soft-deleted, restore
            elseif ($existingPeserta->trashed()) {
                $existingPeserta->restore();
            }
        }

        // Add new peserta yang belum ada
        foreach ($newPesertaIds as $pegawaiId) {
            $peserta = UjikomPeserta::withTrashed()
                ->where('ujikom_permohonan_id', $permohonan->id)
                ->where('pegawai_id', $pegawaiId)
                ->first();

            // Jika belum ada sama sekali, create baru
            if (!$peserta) {
                UjikomPeserta::create([
                    'ujikom_permohonan_id' => $permohonan->id,
                    'pegawai_id' => $pegawaiId,
                ]);
            }
        }

        return redirect()->route('ujikom.show', $permohonan->id)
            ->with('success', 'Permohonan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete ujikom');

        $permohonan = UjikomPermohonan::findOrFail($id);

        // Hanya draft yang bisa dihapus
        if (!$permohonan->bisaDihapus()) {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa dihapus.');
        }

        // Delete file
        if ($permohonan->file_surat_permohonan) {
            Storage::disk('public')->delete($permohonan->file_surat_permohonan);
        }

        $permohonan->delete();

        return redirect()->route('ujikom.index')
            ->with('success', 'Permohonan berhasil dihapus.');
    }

    /**
     * Ajukan permohonan (draft -> diajukan)
     */
    public function ajukan(string $id)
    {
        $this->authorize('create ujikom');

        $permohonan = UjikomPermohonan::findOrFail($id);

        if (!$permohonan->bisaDiajukan()) {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Permohonan tidak bisa diajukan. Pastikan status adalah draft dan ada peserta.');
        }

        $permohonan->update(['status' => 'diajukan']);

        return redirect()->route('ujikom.show', $id)
            ->with('success', 'Permohonan berhasil diajukan.');
    }

    /**
     * Verifikasi permohonan (diajukan -> diverifikasi)
     */
    public function verifikasi(Request $request, string $id)
    {
        $this->authorize('verifikasi ujikom');

        $validated = $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $permohonan = UjikomPermohonan::findOrFail($id);

        if ($permohonan->status !== 'diajukan') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan dengan status diajukan yang bisa diverifikasi.');
        }

        $permohonan->update([
            'status' => 'diverifikasi',
            'catatan_verifikator' => $validated['catatan'],
        ]);

        return redirect()->route('ujikom.show', $id)
            ->with('success', 'Permohonan berhasil diverifikasi.');
    }

    /**
     * Tolak permohonan (diajukan -> draft)
     */
    public function tolak(Request $request, string $id)
    {
        $this->authorize('verifikasi ujikom');

        $validated = $request->validate([
            'catatan' => 'required|string',
        ]);

        $permohonan = UjikomPermohonan::findOrFail($id);

        if ($permohonan->status !== 'diajukan') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan dengan status diajukan yang bisa ditolak.');
        }

        $permohonan->update([
            'status' => 'draft',
            'catatan_verifikator' => $validated['catatan'],
        ]);

        return redirect()->route('ujikom.show', $id)
            ->with('warning', 'Permohonan ditolak dan dikembalikan ke status draft.');
    }

    /**
     * Show form input jadwal
     */
    public function inputJadwal(string $id)
    {
        $this->authorize('verifikasi ujikom');

        $permohonan = UjikomPermohonan::findOrFail($id);

        if ($permohonan->status !== 'diverifikasi') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Jadwal hanya bisa diinput untuk permohonan yang sudah diverifikasi.');
        }

        return view('ujikom.jadwal', compact('permohonan'));
    }

    /**
     * Simpan jadwal (diverifikasi -> terjadwal)
     */
    public function simpanJadwal(Request $request, string $id)
    {
        $this->authorize('verifikasi ujikom');

        $validated = $request->validate([
            'tanggal_jadwal' => 'required|date|after:today',
            'tempat_ujikom' => 'required|string|max:255',
        ]);

        $permohonan = UjikomPermohonan::findOrFail($id);

        if ($permohonan->status !== 'diverifikasi') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Jadwal hanya bisa diinput untuk permohonan yang sudah diverifikasi.');
        }

        $permohonan->update([
            'status' => 'terjadwal',
            'tanggal_jadwal' => $validated['tanggal_jadwal'],
            'tempat_ujikom' => $validated['tempat_ujikom'],
        ]);

        // Generate Berita Acara Verifikasi
        $this->generateBeritaAcaraVerifikasi($permohonan);

        return redirect()->route('ujikom.show', $id)
            ->with('success', 'Jadwal berhasil disimpan dan Berita Acara Verifikasi telah dibuat.');
    }

    /**
     * Konfirmasi selesai uji (terjadwal -> selesai_uji)
     */
    public function konfirmasiSelesai(string $id)
    {
        $this->authorize('verifikasi ujikom');

        $permohonan = UjikomPermohonan::findOrFail($id);

        if ($permohonan->status !== 'terjadwal') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hanya permohonan terjadwal yang bisa dikonfirmasi selesai.');
        }

        $permohonan->update(['status' => 'selesai_uji']);

        return redirect()->route('ujikom.show', $id)
            ->with('success', 'Uji kompetensi berhasil dikonfirmasi selesai.');
    }

    /**
     * Show form input hasil
     */
    public function inputHasil(string $id)
    {
        $this->authorize('input hasil ujikom');

        $permohonan = UjikomPermohonan::with(['peserta.pegawai'])->findOrFail($id);

        if ($permohonan->status !== 'selesai_uji') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hasil hanya bisa diinput untuk permohonan yang sudah selesai uji.');
        }

        return view('ujikom.hasil', compact('permohonan'));
    }

    /**
     * Simpan hasil (selesai_uji -> hasil_diinput)
     */
    public function simpanHasil(Request $request, string $id)
    {
        $this->authorize('input hasil ujikom');

        $validated = $request->validate([
            'hasil' => 'required|array',
            'hasil.*' => 'required|in:lulus,tidak_lulus',
            'catatan' => 'nullable|array',
            'catatan.*' => 'nullable|string',
        ]);

        $permohonan = UjikomPermohonan::with('peserta')->findOrFail($id);

        if ($permohonan->status !== 'selesai_uji') {
            return redirect()->route('ujikom.show', $id)
                ->with('error', 'Hasil hanya bisa diinput untuk permohonan yang sudah selesai uji.');
        }

        // Update hasil peserta
        foreach ($permohonan->peserta as $peserta) {
            $pesertaId = $peserta->id;
            if (isset($validated['hasil'][$pesertaId])) {
                $peserta->update([
                    'hasil' => $validated['hasil'][$pesertaId],
                    'catatan_hasil' => $validated['catatan'][$pesertaId] ?? null,
                ]);
            }
        }

        $permohonan->update(['status' => 'hasil_diinput']);

        return redirect()->route('ujikom.show', $id)
            ->with('success', 'Hasil uji kompetensi berhasil disimpan.');
    }

    /**
     * Generate Berita Acara
     */
    public function generateBA(string $id, string $jenis)
    {
        $this->authorize('verifikasi ujikom');

        $permohonan = UjikomPermohonan::with([
            'unitKerja.regency.province',
            'peserta.pegawai.formasi.jenjang',
        ])->findOrFail($id);

        if ($jenis === 'verifikasi') {
            if ($permohonan->status !== 'diverifikasi' && $permohonan->status !== 'terjadwal' && $permohonan->status !== 'selesai_uji' && $permohonan->status !== 'hasil_diinput' && $permohonan->status !== 'selesai') {
                return redirect()->route('ujikom.show', $id)
                    ->with('error', 'Berita Acara Verifikasi hanya bisa dibuat setelah verifikasi.');
            }

            return $this->generateBeritaAcaraVerifikasi($permohonan, true);
        } elseif ($jenis === 'hasil') {
            if ($permohonan->status !== 'hasil_diinput') {
                return redirect()->route('ujikom.show', $id)
                    ->with('error', 'Berita Acara Hasil hanya bisa dibuat setelah hasil diinput.');
            }

            // Update status ke selesai
            $permohonan->update(['status' => 'selesai']);

            return $this->generateBeritaAcaraHasil($permohonan);
        }

        return redirect()->route('ujikom.show', $id)
            ->with('error', 'Jenis berita acara tidak valid.');
    }

    /**
     * Export detail permohonan as PDF
     */
    public function exportPdf(string $id)
    {
        $this->authorize('view ujikom');

        $permohonan = UjikomPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta.pegawai.formasi.jenjang',
            'peserta.pegawai.unitKerja',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('ujikom.pdf.detail', compact('permohonan'));
        $pdf->setPaper('a4', 'portrait');

        // Ganti "/" dengan "-" untuk nama file yang valid
        $nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
        $filename = 'permohonan-ujikom-' . $nomorPermohonanSafe . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * AJAX: Get pegawai list for Select2
     */
    public function getPegawaiList(Request $request)
    {
        $this->authorize('view ujikom');

        $query = $request->get('q', '');
        $unitKerjaId = $request->get('unit_kerja_id', '');

        $pegawai = Sdmmodels::with(['formasi.jenjang'])
            ->where('aktif', true)
            ->when($unitKerjaId, function ($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId)
                    ->orWhereHas('formasi', function ($f) use ($unitKerjaId) {
                        $f->where('unit_kerja_id', $unitKerjaId);
                    });
            })
            ->where(function ($q) use ($query) {
                $q->where('nama_lengkap', 'like', '%' . $query . '%')
                    ->orWhere('nip', 'like', '%' . $query . '%');
            })
            ->orderBy('nama_lengkap')
            ->limit(20)
            ->get();

        $results = [];
        foreach ($pegawai as $p) {
            $results[] = [
                'id' => $p->id,
                'text' => $p->nama_lengkap . ' - ' . ($p->nip ?? 'N/A') . ' - ' . ($p->formasi?->nama_formasi ?? '-'),
                'nama' => $p->nama_lengkap,
                'nip' => $p->nip,
                'jabatan' => $p->formasi?->nama_formasi ?? '-',
                'jenjang' => $p->formasi?->jenjang?->nama_jenjang ?? '-',
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Helper: Generate Berita Acara Verifikasi
     */
    private function generateBeritaAcaraVerifikasi($permohonan, $returnResponse = false)
    {
        $permohonan->load([
            'unitKerja.regency.province',
            'peserta.pegawai.formasi.jenjang',
        ]);

        $pdf = Pdf::loadView('ujikom.pdf.berita_acara_verifikasi', compact('permohonan'));
        $pdf->setPaper('a4', 'portrait');

        // Ganti "/" dengan "-" untuk nama file yang valid
        $nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
        $fileName = 'ba-verifikasi-' . $nomorPermohonanSafe . '.pdf';
        $filePath = 'ujikom/berita_acara/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($filePath, $pdf->output());

        // Create record in ujikom_berita_acara
        UjikomBeritaAcara::updateOrCreate(
            [
                'ujikom_permohonan_id' => $permohonan->id,
                'jenis' => 'verifikasi',
            ],
            [
                'file_path' => $filePath,
                'dibuat_oleh' => Auth::id(),
            ]
        );

        if ($returnResponse) {
            return $pdf->download($fileName);
        }
    }

    /**
     * Helper: Generate Berita Acara Hasil
     */
    private function generateBeritaAcaraHasil($permohonan)
    {
        $pdf = Pdf::loadView('ujikom.pdf.berita_acara_hasil', compact('permohonan'));
        $pdf->setPaper('a4', 'portrait');

        // Ganti "/" dengan "-" untuk nama file yang valid
        $nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
        $fileName = 'ba-hasil-' . $nomorPermohonanSafe . '.pdf';
        $filePath = 'ujikom/berita_acara/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($filePath, $pdf->output());

        // Create record in ujikom_berita_acara
        UjikomBeritaAcara::updateOrCreate(
            [
                'ujikom_permohonan_id' => $permohonan->id,
                'jenis' => 'hasil',
            ],
            [
                'file_path' => $filePath,
                'dibuat_oleh' => Auth::id(),
            ]
        );

        return $pdf->download($fileName);
    }
}
