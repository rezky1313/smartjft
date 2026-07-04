<?php

namespace App\Http\Controllers;

use App\Models\PengangkatanPermohonan;
use App\Models\PengangkatanPeserta;
use App\Models\PengangkatanSurat;
use App\Models\Sdmmodels;
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;
use App\Models\UjikomPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PengangkatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view pengangkatan');

        $query = PengangkatanPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta',
        ]);

        // Filter by jalur
        if ($request->has('jalur') && $request->jalur) {
            $query->where('jalur', $request->jalur);
        }

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
        $tahuns = PengangkatanPermohonan::selectRaw('YEAR(tanggal_permohonan) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        $jalurs = [
            'pengangkatan_pertama'  => 'Pengangkatan Pertama',
            'inpasing'              => 'Penyesuaian/Inpasing',
            'kenaikan_jenjang'      => 'Kenaikan Jenjang',
            'promosi'               => 'Promosi',
            'perpindahan_kategori'  => 'Perpindahan Kategori',
            'perpindahan_jabatan'   => 'Perpindahan dari Jabatan Lain',
            'pengangkatan_kembali'  => 'Pengangkatan Kembali',
        ];

        return view('pengangkatan.index', compact('permohonan', 'unitKerja', 'tahuns', 'jalurs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create pengangkatan');

        $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);
        $formasi = Formasijabatan::with(['jenjang', 'unitkerja'])->get();
        $jalurs = [
            'pengangkatan_pertama'  => 'Pengangkatan Pertama',
            'inpasing'              => 'Penyesuaian/Inpasing',
            'kenaikan_jenjang'      => 'Kenaikan Jenjang',
            'promosi'               => 'Promosi',
            'perpindahan_kategori'  => 'Perpindahan Kategori',
            'perpindahan_jabatan'   => 'Perpindahan dari Jabatan Lain',
            'pengangkatan_kembali'  => 'Pengangkatan Kembali',
        ];

        return view('pengangkatan.create', compact('unitKerja', 'formasi', 'jalurs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create pengangkatan');

        $validated = $request->validate([
            'jalur' => 'required|in:pengangkatan_pertama,inpasing,kenaikan_jenjang,promosi,perpindahan_kategori,perpindahan_jabatan,pengangkatan_kembali',
            'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
            'tanggal_permohonan' => 'required|date',
            'file_surat_permohonan' => 'required|file|mimes:pdf|max:2048',
            'peserta' => 'required|array|min:1',
            'peserta.*.pegawai_id' => 'required|exists:sumber_daya_manusia,id',
            'peserta.*.jabatan_tujuan_id' => 'required|exists:formasi_jabatan,id',
            'peserta.*.jenjang_tujuan' => 'required|string',
            'peserta.*.unit_kerja_tujuan_id' => 'required|exists:rumahsakits,no_rs',
            'ajukan_sekarang' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Generate nomor permohonan
            $nomorPermohonan = PengangkatanPermohonan::generateNomorPermohonan($validated['tanggal_permohonan']);

            // Upload file surat permohonan
            $filePath = null;
            if ($request->hasFile('file_surat_permohonan')) {
                $file = $request->file('file_surat_permohonan');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('pengangkatan/surat_permohonan', $fileName, 'public');
            }

            // Create permohonan
            $status = $request->has('ajukan_sekarang') && $request->ajukan_sekarang ? 'diajukan' : 'draft';
            $permohonan = PengangkatanPermohonan::create([
                'nomor_permohonan' => $nomorPermohonan,
                'jalur' => $validated['jalur'],
                'unit_kerja_id' => $validated['unit_kerja_id'],
                'file_surat_permohonan' => $filePath,
                'tanggal_permohonan' => $validated['tanggal_permohonan'],
                'status' => $status,
                'created_by' => Auth::id(),
            ]);

            $tahunSekarang = now()->year;

            // Create peserta dengan validasi
            foreach ($validated['peserta'] as $pesertaData) {
                $pegawai = Sdmmodels::with(['formasi.jenjang', 'unitKerja'])->find($pesertaData['pegawai_id']);
                $formasiTujuan = Formasijabatan::find($pesertaData['jabatan_tujuan_id']);

                // Validasi 1: Cek Formasi
                $cekFormasi = PengangkatanPeserta::cekFormasi(
                    $pesertaData['jabatan_tujuan_id'],
                    $pesertaData['unit_kerja_tujuan_id'],
                    $tahunSekarang
                );

                // Validasi 2: Cek Ujikom
                $cekUjikom = PengangkatanPeserta::cekUjikom($pesertaData['pegawai_id']);

                PengangkatanPeserta::create([
                    'pengangkatan_permohonan_id' => $permohonan->id,
                    'pegawai_id' => $pesertaData['pegawai_id'],
                    'jabatan_asal' => $pegawai->formasi?->nama_formasi,
                    'jenjang_asal' => $pegawai->formasi?->jenjang?->nama_jenjang,
                    'unit_kerja_asal' => $pegawai->unit_kerja_id,
                    'jabatan_tujuan_id' => $pesertaData['jabatan_tujuan_id'],
                    'jenjang_tujuan' => $pesertaData['jenjang_tujuan'],
                    'unit_kerja_tujuan_id' => $pesertaData['unit_kerja_tujuan_id'],
                    'ujikom_peserta_id' => $cekUjikom['ujikom_peserta_id'],
                    'status_validasi_formasi' => $cekFormasi['tersedia'] ? 'tersedia' : 'tidak_tersedia',
                    'status_validasi_ujikom' => $cekUjikom['memenuhi'] ? 'memenuhi' : 'tidak_memenuhi',
                ]);
            }

            DB::commit();

            $message = $status === 'diajukan'
                ? 'Permohonan berhasil dibuat dan diajukan.'
                : 'Permohonan berhasil disimpan sebagai draft.';

            return redirect()->route('pengangkatan.show', $permohonan->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('view pengangkatan');

        $permohonan = PengangkatanPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta.pegawai.formasi.jenjang',
            'peserta.pegawai.unitKerja',
            'peserta.jabatanTujuan.jenjang',
            'peserta.unitKerjaTujuan',
            'peserta.ujikomPeserta',
            'surat.dibuatOleh',
        ])->findOrFail($id);

        // Get timeline steps
        $statusOrder = ['draft', 'diajukan', 'diverifikasi', 'draft_surat', 'paraf_katim', 'paraf_kabid', 'tanda_tangan', 'penomoran', 'selesai'];
        $currentStatusIndex = array_search($permohonan->status, $statusOrder);

        return view('pengangkatan.show', compact('permohonan', 'statusOrder', 'currentStatusIndex'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->authorize('edit pengangkatan');

        $permohonan = PengangkatanPermohonan::with(['peserta.pegawai', 'unitKerja'])->findOrFail($id);

        // Hanya draft yang bisa diedit
        if (!$permohonan->bisaDiedit()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa diedit.');
        }

        $unitKerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs', 'nama_rumahsakit']);
        $formasi = Formasijabatan::with(['jenjang', 'unitkerja'])->get();
        $jalurs = [
            'pengangkatan_pertama'  => 'Pengangkatan Pertama',
            'inpasing'              => 'Penyesuaian/Inpasing',
            'kenaikan_jenjang'      => 'Kenaikan Jenjang',
            'promosi'               => 'Promosi',
            'perpindahan_kategori'  => 'Perpindahan Kategori',
            'perpindahan_jabatan'   => 'Perpindahan dari Jabatan Lain',
            'pengangkatan_kembali'  => 'Pengangkatan Kembali',
        ];

        return view('pengangkatan.edit', compact('permohonan', 'unitKerja', 'formasi', 'jalurs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('edit pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        // Hanya draft yang bisa diedit
        if (!$permohonan->bisaDiedit()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa diedit.');
        }

        $validated = $request->validate([
            'jalur' => 'required|in:pengangkatan_pertama,inpasing,kenaikan_jenjang,promosi,perpindahan_kategori,perpindahan_jabatan,pengangkatan_kembali',
            'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
            'tanggal_permohonan' => 'required|date',
            'file_surat_permohonan' => 'nullable|file|mimes:pdf|max:2048',
            'peserta' => 'required|array|min:1',
            'peserta.*.pegawai_id' => 'required|exists:sumber_daya_manusia,id',
            'peserta.*.jabatan_tujuan_id' => 'required|exists:formasi_jabatan,id',
            'peserta.*.jenjang_tujuan' => 'required|string',
            'peserta.*.unit_kerja_tujuan_id' => 'required|exists:rumahsakits,no_rs',
        ]);

        DB::beginTransaction();
        try {
            // Update file jika ada
            if ($request->hasFile('file_surat_permohonan')) {
                // Delete old file
                if ($permohonan->file_surat_permohonan) {
                    Storage::disk('public')->delete($permohonan->file_surat_permohonan);
                }

                $file = $request->file('file_surat_permohonan');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('pengangkatan/surat_permohonan', $fileName, 'public');
                $validated['file_surat_permohonan'] = $filePath;
            } else {
                $validated['file_surat_permohonan'] = $permohonan->file_surat_permohonan;
            }

            // Regenerate nomor permohonan jika tanggal berubah
            if ($permohonan->tanggal_permohonan != $validated['tanggal_permohonan']) {
                $validated['nomor_permohonan'] = PengangkatanPermohonan::generateNomorPermohonan($validated['tanggal_permohonan']);
            }

            $permohonan->update([
                'jalur' => $validated['jalur'],
                'unit_kerja_id' => $validated['unit_kerja_id'],
                'file_surat_permohonan' => $validated['file_surat_permohonan'],
                'tanggal_permohonan' => $validated['tanggal_permohonan'],
                'nomor_permohonan' => $validated['nomor_permohonan'] ?? $permohonan->nomor_permohonan,
            ]);

            $tahunSekarang = now()->year;

            // Delete all existing peserta
            PengangkatanPeserta::where('pengangkatan_permohonan_id', $permohonan->id)->delete();

            // Create new peserta dengan validasi
            foreach ($validated['peserta'] as $pesertaData) {
                $pegawai = Sdmmodels::with(['formasi.jenjang', 'unitKerja'])->find($pesertaData['pegawai_id']);

                // Validasi 1: Cek Formasi
                $cekFormasi = PengangkatanPeserta::cekFormasi(
                    $pesertaData['jabatan_tujuan_id'],
                    $pesertaData['unit_kerja_tujuan_id'],
                    $tahunSekarang
                );

                // Validasi 2: Cek Ujikom
                $cekUjikom = PengangkatanPeserta::cekUjikom($pesertaData['pegawai_id']);

                PengangkatanPeserta::create([
                    'pengangkatan_permohonan_id' => $permohonan->id,
                    'pegawai_id' => $pesertaData['pegawai_id'],
                    'jabatan_asal' => $pegawai->formasi?->nama_formasi,
                    'jenjang_asal' => $pegawai->formasi?->jenjang?->nama_jenjang,
                    'unit_kerja_asal' => $pegawai->unit_kerja_id,
                    'jabatan_tujuan_id' => $pesertaData['jabatan_tujuan_id'],
                    'jenjang_tujuan' => $pesertaData['jenjang_tujuan'],
                    'unit_kerja_tujuan_id' => $pesertaData['unit_kerja_tujuan_id'],
                    'ujikom_peserta_id' => $cekUjikom['ujikom_peserta_id'],
                    'status_validasi_formasi' => $cekFormasi['tersedia'] ? 'tersedia' : 'tidak_tersedia',
                    'status_validasi_ujikom' => $cekUjikom['memenuhi'] ? 'memenuhi' : 'tidak_memenuhi',
                ]);
            }

            DB::commit();

            return redirect()->route('pengangkatan.show', $permohonan->id)
                ->with('success', 'Permohonan berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        // Hanya draft yang bisa dihapus
        if (!$permohonan->bisaDihapus()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Hanya permohonan dengan status draft yang bisa dihapus.');
        }

        // Delete file
        if ($permohonan->file_surat_permohonan) {
            Storage::disk('public')->delete($permohonan->file_surat_permohonan);
        }

        $permohonan->delete();

        return redirect()->route('pengangkatan.index')
            ->with('success', 'Permohonan berhasil dihapus.');
    }

    /**
     * Ajukan permohonan (draft -> diajukan)
     */
    public function ajukan(string $id)
    {
        $this->authorize('create pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if (!$permohonan->bisaDiajukan()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Permohonan tidak bisa diajukan. Pastikan status adalah draft dan ada peserta.');
        }

        $permohonan->update(['status' => 'diajukan']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan berhasil diajukan.');
    }

    /**
     * Verifikasi permohonan (diajukan -> diverifikasi)
     */
    public function verifikasi(Request $request, string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $validated = $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if (!$permohonan->bisaDiverifikasi()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Hanya permohonan dengan status diajukan yang bisa diverifikasi.');
        }

        $permohonan->update([
            'status' => 'diverifikasi',
            'catatan_verifikator' => $validated['catatan'],
        ]);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan berhasil diverifikasi.');
    }

    /**
     * Tolak permohonan (diajukan -> draft)
     */
    public function tolak(Request $request, string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $validated = $request->validate([
            'catatan' => 'required|string',
        ]);

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if (!$permohonan->bisaDiverifikasi()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Hanya permohonan dengan status diajukan yang bisa ditolak.');
        }

        $permohonan->update([
            'status' => 'draft',
            'catatan_verifikator' => $validated['catatan'],
        ]);

        return redirect()->route('pengangkatan.show', $id)
            ->with('warning', 'Permohonan ditolak dan dikembalikan ke status draft.');
    }

    /**
     * Buat Draft Surat Pertimbangan (diverifikasi -> draft_surat)
     */
    public function buatDraftSurat(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::with([
            'unitKerja',
            'peserta.pegawai',
            'peserta.jabatanTujuan',
            'peserta.unitKerjaTujuan',
        ])->findOrFail($id);

        if (!$permohonan->bisaBuatDraftSurat()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Draft surat hanya bisa dibuat untuk permohonan yang sudah diverifikasi.');
        }

        DB::beginTransaction();
        try {
            // Generate PDF
            $pdf = Pdf::loadView('pengangkatan.pdf.surat_pertimbangan', compact('permohonan'));
            $pdf->setPaper('a4', 'portrait');

            $nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
            $fileName = 'surat-pertimbangan-' . $nomorPermohonanSafe . '.pdf';
            $filePath = 'pengangkatan/surat_pertimbangan/' . $fileName;

            // Save to storage
            Storage::disk('public')->put($filePath, $pdf->output());

            // Create or update surat record
            PengangkatanSurat::updateOrCreate(
                ['pengangkatan_permohonan_id' => $permohonan->id],
                [
                    'file_path' => $filePath,
                    'dibuat_oleh' => Auth::id(),
                ]
            );

            // Update status permohonan
            $permohonan->update(['status' => 'draft_surat']);

            DB::commit();

            return redirect()->route('pengangkatan.show', $id)
                ->with('success', 'Draft Surat Pertimbangan berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Konfirmasi Paraf Katim (draft_surat -> paraf_katim)
     */
    public function konfirmasiParafKatim(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if ($permohonan->status !== 'draft_surat') {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Status permohonan harus Draft Surat untuk konfirmasi Paraf Katim.');
        }

        $permohonan->update(['status' => 'paraf_katim']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Paraf Katim berhasil dikonfirmasi.');
    }

    /**
     * Konfirmasi Paraf Kabid (paraf_katim -> paraf_kabid)
     */
    public function konfirmasiParafKabid(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if ($permohonan->status !== 'paraf_katim') {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Status permohonan harus Paraf Katim untuk konfirmasi Paraf Kabid.');
        }

        $permohonan->update(['status' => 'paraf_kabid']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Paraf Kabid berhasil dikonfirmasi.');
    }

    /**
     * Konfirmasi Tanda Tangan (paraf_kabid -> tanda_tangan)
     */
    public function konfirmasiTtd(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if ($permohonan->status !== 'paraf_kabid') {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Status permohonan harus Paraf Kabid untuk konfirmasi Tanda Tangan.');
        }

        $permohonan->update(['status' => 'tanda_tangan']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Tanda Tangan berhasil dikonfirmasi.');
    }

    /**
     * Show form input nomor surat
     */
    public function inputNomor(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if (!$permohonan->bisaDinomorikan()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Nomor surat hanya bisa diinput untuk permohonan yang sudah ditandatangani.');
        }

        return view('pengangkatan.nomor', compact('permohonan'));
    }

    /**
     * Simpan nomor surat (tanda_tangan -> penomoran)
     */
    public function simpanNomor(Request $request, string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:255',
        ]);

        $permohonan = PengangkatanPermohonan::findOrFail($id);

        if (!$permohonan->bisaDinomorikan()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Nomor surat hanya bisa diinput untuk permohonan yang sudah ditandatangani.');
        }

        // Update surat record
        $surat = PengangkatanSurat::where('pengangkatan_permohonan_id', $permohonan->id)->first();
        if ($surat) {
            $surat->update(['nomor_surat' => $validated['nomor_surat']]);
        }

        // Update status permohonan
        $permohonan->update(['status' => 'penomoran']);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Nomor surat berhasil disimpan.');
    }

    /**
     * Selesaikan permohonan (penomoran -> selesai) + update data pegawai
     */
    public function selesaikan(string $id)
    {
        $this->authorize('verifikasi pengangkatan');

        $permohonan = PengangkatanPermohonan::with([
            'peserta.pegawai',
            'peserta.jabatanTujuan',
        ])->findOrFail($id);

        if (!$permohonan->bisaDiselesaikan()) {
            return redirect()->route('pengangkatan.show', $id)
                ->with('error', 'Permohonan hanya bisa diselesaikan pada status penomoran.');
        }

        DB::beginTransaction();
        try {
            // Update data pegawai untuk semua peserta
            foreach ($permohonan->peserta as $peserta) {
                $pegawai = $peserta->pegawai;

                // Simpan data lama untuk histori
                $jabatanLamaId = $pegawai->formasi_jabatan_id;
                $unitKerjaLamaId = $pegawai->unit_kerja_id;

                // Update data pegawai
                $pegawai->update([
                    'formasi_jabatan_id' => $peserta->jabatan_tujuan_id,
                    'unit_kerja_id' => $peserta->unit_kerja_tujuan_id,
                ]);

                // Recalculate status_formasi pada jabatan lama
                if ($jabatanLamaId) {
                    $formasiLama = Formasijabatan::find($jabatanLamaId);
                    if ($formasiLama) {
                        $terisiBaru = $formasiLama->sdmAktif()->count();
                        $sisaBaru = ($formasiLama->kuota ?? 0) - $terisiBaru;

                        // Update status_formasi pegawai lain di jabatan lama
                        Sdmmodels::where('formasi_jabatan_id', $jabatanLamaId)
                            ->where('id', '!=', $pegawai->id)
                            ->where('aktif', true)
                            ->update([
                                'status_formasi' => $sisaBaru >= 0 ? 'terpenuhi' : 'di_luar_formasi'
                            ]);
                    }
                }

                // Recalculate status_formasi pada jabatan baru
                $formasiBaru = Formasijabatan::find($peserta->jabatan_tujuan_id);
                if ($formasiBaru) {
                    $terisiBaru = $formasiBaru->sdmAktif()->count();
                    $sisaBaru = ($formasiBaru->kuota ?? 0) - $terisiBaru;

                    // Update status_formasi pegawai
                    $pegawai->update([
                        'status_formasi' => $sisaBaru >= 0 ? 'terpenuhi' : 'di_luar_formasi'
                    ]);

                    // Update status_formasi pegawai lain di jabatan baru
                    Sdmmodels::where('formasi_jabatan_id', $peserta->jabatan_tujuan_id)
                        ->where('id', '!=', $pegawai->id)
                        ->where('aktif', true)
                        ->update([
                            'status_formasi' => $sisaBaru >= 0 ? 'terpenuhi' : 'di_luar_formasi'
                        ]);
                }
            }

            // Update status permohonan
            $permohonan->update(['status' => 'selesai']);

            DB::commit();

            return redirect()->route('pengangkatan.show', $id)
                ->with('success', 'Permohonan berhasil diselesaikan. Data pegawai telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Validasi peserta (cek formasi & ujikom)
     */
    public function validasiPeserta(Request $request)
    {
        $this->authorize('create pengangkatan');

        $validated = $request->validate([
            'pegawai_id' => 'required|exists:sumber_daya_manusia,id',
            'jabatan_tujuan_id' => 'required|exists:formasi_jabatan,id',
            'unit_kerja_tujuan_id' => 'required|exists:rumahsakits,no_rs',
        ]);

        $tahunSekarang = now()->year;

        // Validasi 1: Cek Formasi
        $cekFormasi = PengangkatanPeserta::cekFormasi(
            $validated['jabatan_tujuan_id'],
            $validated['unit_kerja_tujuan_id'],
            $tahunSekarang
        );

        // Validasi 2: Cek Ujikom
        $cekUjikom = PengangkatanPeserta::cekUjikom($validated['pegawai_id']);

        return response()->json([
            'formasi' => $cekFormasi,
            'ujikom' => $cekUjikom,
        ]);
    }

    /**
     * Export detail permohonan as PDF
     */
    public function exportPdf(string $id)
    {
        $this->authorize('view pengangkatan');

        $permohonan = PengangkatanPermohonan::with([
            'unitKerja.regency.province',
            'createdBy',
            'peserta.pegawai.formasi.jenjang',
            'peserta.pegawai.unitKerja',
            'peserta.jabatanTujuan.jenjang',
            'peserta.unitKerjaTujuan',
            'peserta.ujikomPeserta',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pengangkatan.pdf.detail', compact('permohonan'));
        $pdf->setPaper('a4', 'portrait');

        $nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
        $filename = 'permohonan-pengangkatan-' . $nomorPermohonanSafe . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * AJAX: Get pegawai by unit_kerja_id (untuk dropdown di form create)
     */
    public function getPegawai(Request $request)
    {
        $unitKerjaId = $request->get('unit_kerja_id', '');

        $pegawai = Sdmmodels::with(['formasi.jenjang'])
            ->where('aktif', true)
            ->when($unitKerjaId, function ($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId)
                    ->orWhereHas('formasi', function ($f) use ($unitKerjaId) {
                        $f->where('unit_kerja_id', $unitKerjaId);
                    });
            })
            ->orderBy('nama_lengkap')
            ->limit(50)
            ->get();

        $results = [];
        foreach ($pegawai as $p) {
            $results[] = [
                'id' => $p->id,
                'text' => $p->nama_lengkap . ' - ' . ($p->nip ?? 'N/A'),
                'nama' => $p->nama_lengkap,
                'nip' => $p->nip,
                'jabatan' => $p->formasi?->nama_formasi ?? '-',
                'jenjang' => $p->formasi?->jenjang?->nama_jenjang ?? '-',
                'unit_kerja_id' => $p->unit_kerja_id,
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX: Get pegawai list for Select2 (dengan search query)
     */
    public function getPegawaiList(Request $request)
    {
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
                'unit_kerja_id' => $p->unit_kerja_id,
            ];
        }

        return response()->json(['results' => $results]);
    }
}
