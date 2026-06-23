<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UjikomPendaftaran;
use App\Models\UjikomPendaftaranPeserta;
use App\Models\UjikomPendaftaranBerkas;
use App\Models\UjikomJadwal;
use App\Models\UjikomPersyaratan;
use App\Models\Sdmmodels;
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UjikomPendaftaranController extends Controller
{
    // ─── AJAX Helpers ─────────────────────────────────────────────────────────

    /** Info jadwal + persyaratan untuk card di create form */
    public function getJadwalInfo($jadwalId)
    {
        $jadwal = UjikomJadwal::with('persyaratan')->find($jadwalId);
        if (!$jadwal) {
            return response()->json(['error' => 'Jadwal tidak ditemukan'], 404);
        }

        // Hitung sisa kuota dari pendaftaran yang sudah masuk
        $terdaftar = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereNotIn('status', ['ditolak_admin_unit', 'ditolak_pusbin', 'draft']);
        })->count();

        $sisaKuota = max(0, $jadwal->kuota - $terdaftar);

        return response()->json([
            'judul'          => $jadwal->judul,
            'tanggal_mulai'  => $jadwal->tanggal_mulai->format('d F Y'),
            'tanggal_selesai'=> $jadwal->tanggal_selesai->format('d F Y'),
            'tempat'         => $jadwal->tempat,
            'kuota'          => $jadwal->kuota,
            'terdaftar'      => $terdaftar,
            'sisa_kuota'     => $sisaKuota,
            'persyaratan'    => $jadwal->persyaratan->map(fn($p) => [
                'id'          => $p->id,
                'nama_syarat' => $p->nama_syarat,
                'keterangan'  => $p->keterangan,
                'file_contoh' => $p->file_contoh ? asset('storage/' . $p->file_contoh) : null,
            ]),
        ]);
    }

    /** Daftar pegawai berdasarkan unit kerja untuk Select2 */
    /** AJAX — daftar semua pegawai per unit kerja (untuk populate dropdown) */
    public function getPegawai(Request $request)
    {
        $unitKerjaId = $request->unit_kerja_id;

        $pegawais = Sdmmodels::where('unit_kerja_id', $unitKerjaId)
            ->where('aktif', true)
            ->with('formasi.jenjang')
            ->select('id', 'nama_lengkap', 'nip', 'pangkat_golongan', 'formasi_jabatan_id', 'unit_kerja_id')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn($p) => [
                'id'      => $p->id,
                'text'    => "{$p->nama_lengkap} — {$p->nip}",
                'nama'    => $p->nama_lengkap,
                'nip'     => $p->nip,
                'jabatan' => $p->formasi?->nama_formasi ?? '-',
                'jenjang' => $p->formasi?->jenjang?->nama_jenjang ?? '-',
            ]);

        return response()->json(['results' => $pegawais]);
    }

    /** AJAX — jabatan/jenjang tujuan ujikom untuk satu pegawai */
    public function getJabatanTujuan(Request $request)
    {
        $pegawai = Sdmmodels::with('formasi.jenjang')->find($request->pegawai_id);

        if (!$pegawai || !$pegawai->formasi) {
            return response()->json([
                'jabatan_sekarang'        => '-',
                'jenjang_sekarang'        => '-',
                'jenjang_tujuan_otomatis' => null,
                'pesan_khusus'            => 'Pegawai tidak memiliki formasi jabatan.',
                'opsi_jabatan'            => [],
                'sisa'   => null,
                'status' => 'tidak_tersedia',
                'label'  => 'Tidak memiliki formasi',
                'badge'  => 'danger',
            ]);
        }

        $formasiSekarang = $pegawai->formasi;
        $jenjangSekarang = $formasiSekarang->jenjang;
        $namaFormasi     = $formasiSekarang->nama_formasi;
        $sisa            = $formasiSekarang->sisa;

        // Cari jenjang_id setingkat di atas (same nama_formasi, jenjang_id berikutnya)
        $jenjangTujuanId = null;
        $pesanKhusus     = null;

        $jenjangIdList = Formasijabatan::where('nama_formasi', $namaFormasi)
            ->orderBy('jenjang_id')
            ->pluck('jenjang_id')
            ->unique()
            ->values();

        $currentIdx = $jenjangIdList->search($formasiSekarang->jenjang_id);
        if ($currentIdx !== false && $currentIdx < $jenjangIdList->count() - 1) {
            $jenjangTujuanId = $jenjangIdList[$currentIdx + 1];
        } else {
            $pesanKhusus = $jenjangSekarang?->kategori === 'Terampil'
                ? 'Jenjang tertinggi keterampilan.'
                : 'Jenjang tertinggi. Tidak dapat naik jenjang lagi.';
        }

        // Ambil semua formasi di unit kerja pegawai sebagai opsi
        $formasiUnitKerja = Formasijabatan::where('unit_kerja_id', $pegawai->unit_kerja_id)
            ->with('jenjang')
            ->orderBy('nama_formasi')
            ->orderBy('jenjang_id')
            ->get();

        $formasiSama = $formasiUnitKerja->where('nama_formasi', $namaFormasi);
        $formasiLain = $formasiUnitKerja->where('nama_formasi', '!=', $namaFormasi);

        $buildOption = fn($f) => [
            'id'             => $f->id,
            'text'           => ($f->nama_formasi ?? '-') . ' / ' . ($f->jenjang?->nama_jenjang ?? '-'),
            'jenjang'        => $f->jenjang?->nama_jenjang ?? '-',
            'jabatan_nama'   => $f->nama_formasi ?? '-',
            'is_rekomendasi' => $f->jenjang_id === $jenjangTujuanId,
        ];

        $opsiJabatan = [];
        if ($formasiSama->count() > 0) {
            $opsiJabatan[] = ['label' => '-- Jabatan Sama --', 'options' => $formasiSama->map($buildOption)->values()];
        }
        if ($formasiLain->count() > 0) {
            $opsiJabatan[] = ['label' => '-- Jabatan Lain --', 'options' => $formasiLain->map($buildOption)->values()];
        }

        return response()->json([
            'jabatan_sekarang'        => $namaFormasi,
            'jenjang_sekarang'        => $jenjangSekarang?->nama_jenjang ?? '-',
            'jenjang_tujuan_otomatis' => $jenjangTujuanId,
            'pesan_khusus'            => $pesanKhusus,
            'opsi_jabatan'            => $opsiJabatan,
            'sisa'   => $sisa,
            'status' => $sisa > 0 ? 'tersedia' : 'tidak_tersedia',
            'label'  => $sisa > 0 ? "Tersedia ({$sisa} sisa)" : 'Tidak Tersedia',
            'badge'  => $sisa > 0 ? 'success' : 'danger',
        ]);
    }

    /** AJAX — info formasi per unit kerja (untuk tabel info) */
    public function getFormasi(Request $request)
    {
        $unitKerjaId = $request->unit_kerja_id;
        $tahun       = date('Y');

        $formasiList = Formasijabatan::where('unit_kerja_id', $unitKerjaId)
          //  ->where('tahun_formasi', $tahun)
            ->with('jenjang')
            ->get()
            ->map(function ($f) {
                $sisa = $f->sisa;
                return [
                    'jabatan' => ($f->nama_formasi ?? '-') . ' / ' . ($f->jenjang?->nama_jenjang ?? '-'),
                    'kuota'   => $f->kuota,
                    'terisi'  => $f->terisi,
                    'sisa'    => $sisa,
                    'status'  => $sisa > 0 ? 'tersedia' : ($sisa == 0 ? 'penuh' : 'minus'),
                ];
            });

        return response()->json(['data' => $formasiList]);
    }

    public function getPegawaiList(Request $request)
    {
        $unitKerjaId = $request->unit_kerja_id;
        $search      = $request->get('q', '');

        $query = Sdmmodels::where('unit_kerja_id', $unitKerjaId)
            ->where('aktif', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $pegawais = $query->with('formasi.jenjang')
            ->select('id', 'nama_lengkap', 'nip', 'pangkat_golongan', 'formasi_jabatan_id', 'unit_kerja_id')
            ->orderBy('nama_lengkap')
            ->limit(50)
            ->get();

        return response()->json($pegawais->map(fn($p) => [
            'id'      => $p->id,
            'text'    => "{$p->nama_lengkap} ({$p->nip})",
            'nama'    => $p->nama_lengkap,
            'nip'     => $p->nip,
            'pangkat' => $p->pangkat_golongan ?? '-',
            'jenjang' => $p->formasi?->jenjang?->nama_jenjang ?? '-',
            'jabatan' => $p->formasi?->nama_formasi ?? '-',
        ]));
    }

    /** Cek sisa formasi untuk satu pegawai */
    public function cekFormasi($pegawaiId)
    {
        $pegawai = Sdmmodels::find($pegawaiId);
        if (!$pegawai || !$pegawai->formasi_jabatan_id) {
            return response()->json([
                'sisa'   => null,
                'status' => 'tidak_tersedia',
                'label'  => 'Tidak memiliki formasi',
                'badge'  => 'danger',
            ]);
        }

        $formasi = Formasijabatan::find($pegawai->formasi_jabatan_id);
        $sisa    = $formasi ? $formasi->sisa : 0;

        return response()->json([
            'sisa'        => $sisa,
            'nama_jabatan'=> $formasi?->nama_formasi ?? '-',
            'kuota'       => $formasi?->kuota ?? 0,
            'status'      => $sisa > 0 ? 'tersedia' : 'tidak_tersedia',
            'label'       => $sisa > 0 ? "Tersedia ({$sisa} sisa)" : 'Tidak Tersedia',
            'badge'       => $sisa > 0 ? 'success' : 'danger',
        ]);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $user  = Auth::user();
        $query = UjikomPendaftaran::with(['jadwal', 'unitKerja', 'peserta']);

        if (!$user->hasRole(['admin', 'super_admin'])) {
            $query->where('dibuat_oleh', $user->id);
        }

        if (request('status'))   $query->where('status', request('status'));
        if (request('jadwal_id')) $query->where('ujikom_jadwal_id', request('jadwal_id'));

        $pendaftarans = $query->orderBy('created_at', 'desc')->paginate(15);
        $jadwals      = UjikomJadwal::orderBy('tanggal_mulai', 'desc')->get();

        return view('ujikom.pendaftaran.index', compact('pendaftarans', 'jadwals'));
    }

    public function create()
    {
        $jadwals     = UjikomJadwal::where('status', 'published')
                        ->orderBy('tanggal_mulai', 'desc')->get();
        $unitKerjas  = Rumahsakit::orderBy('nama_rumahsakit')->get();

        return view('ujikom.pendaftaran.create', compact('jadwals', 'unitKerjas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ujikom_jadwal_id'         => 'required|exists:ujikom_jadwal,id',
            'unit_kerja_id'            => 'required|exists:rumahsakits,no_rs',
            'jenis_pendaftaran'        => 'required|in:mandiri,batch',
            'peserta'                  => 'required|array|min:1',
            'peserta.*.pegawai_id'     => 'required|exists:sumber_daya_manusia,id',
        ]);

        $status      = $request->has('ajukan') ? 'diajukan_admin_unit' : 'draft';
        $kode        = UjikomPendaftaran::generateKode();

        $pendaftaran = UjikomPendaftaran::create([
            'kode_pendaftaran' => $kode,
            'ujikom_jadwal_id' => $request->ujikom_jadwal_id,
            'unit_kerja_id'    => $request->unit_kerja_id,
            'jenis_pendaftaran'=> $request->jenis_pendaftaran,
            'status'           => $status,
            'dibuat_oleh'      => Auth::id(),
        ]);

        foreach ($request->peserta as $idx => $pesertaData) {
            $jabatanTujuan     = isset($pesertaData['formasi_tujuan_id'])
                ? Formasijabatan::with('jenjang')->find($pesertaData['formasi_tujuan_id'])
                : null;

            $peserta = UjikomPendaftaranPeserta::create([
                'ujikom_pendaftaran_id' => $pendaftaran->id,
                'pegawai_id'            => $pesertaData['pegawai_id'],
                'jabatan_tujuan_id'     => $jabatanTujuan?->id,
                'jenjang_tujuan'        => $jabatanTujuan?->jenjang?->nama_jenjang,
                'jabatan_tujuan_nama'   => $jabatanTujuan
                                            ? ($jabatanTujuan->nama_formasi . ' ' . ($jabatanTujuan->jenjang?->nama_jenjang ?? ''))
                                            : null,
                'sisa_formasi'          => $pesertaData['sisa_formasi'] ?? null,
                'status_formasi'        => $pesertaData['status_formasi'] ?? 'tidak_tersedia',
            ]);

            // Upload berkas per persyaratan untuk peserta ini
            if ($request->hasFile("berkas.{$idx}")) {
                foreach ($request->file("berkas.{$idx}") as $persyaratanId => $file) {
                    if (!$file || !$file->isValid()) continue;
                    $persyaratan = UjikomPersyaratan::find($persyaratanId);
                    if (!$persyaratan) continue;

                    $path = $file->store('ujikom/berkas', 'public');

                    UjikomPendaftaranBerkas::create([
                        'ujikom_pendaftaran_id' => $pendaftaran->id,
                        'pegawai_id'            => $pesertaData['pegawai_id'],
                        'ujikom_persyaratan_id' => $persyaratanId,
                        'nama_berkas'           => $persyaratan->nama_syarat,
                        'file_path'             => $path,
                    ]);
                }
            }
        }

        $msg = $status === 'diajukan_admin_unit'
            ? 'Permohonan berhasil diajukan ke Admin Unit.'
            : 'Permohonan berhasil disimpan sebagai draft.';

        return redirect()->route('ujikom.permohonan.show', $pendaftaran->id)->with('success', $msg);
    }

    public function show($id)
    {
        $pendaftaran = UjikomPendaftaran::with([
            'jadwal.persyaratan',
            'unitKerja',
            'pembuat',
            'peserta.pegawai.formasi.jenjang',
            'berkas.persyaratan',
            'berkas.pegawai',
        ])->findOrFail($id);

        $this->authorizeView($pendaftaran);

        // Group berkas by pegawai_id untuk tampilan per peserta
        $berkasPerPegawai = $pendaftaran->berkas->groupBy('pegawai_id');

        return view('ujikom.pendaftaran.show', compact('pendaftaran', 'berkasPerPegawai'));
    }

    public function edit($id)
    {
        $pendaftaran = UjikomPendaftaran::with(['peserta.pegawai', 'jadwal.persyaratan', 'berkas'])
            ->findOrFail($id);

        $this->authorizeEdit($pendaftaran);

        $jadwals    = UjikomJadwal::where('status', 'published')->orderBy('tanggal_mulai', 'desc')->get();
        $unitKerjas = Rumahsakit::orderBy('nama_rumahsakit')->get();

        return view('ujikom.pendaftaran.edit', compact('pendaftaran', 'jadwals', 'unitKerjas'));
    }

    public function update(Request $request, $id)
    {
        $pendaftaran = UjikomPendaftaran::with('peserta', 'berkas')->findOrFail($id);
        $this->authorizeEdit($pendaftaran);

        $request->validate([
            'ujikom_jadwal_id'         => 'required|exists:ujikom_jadwal,id',
            'unit_kerja_id'            => 'required|exists:rumahsakits,no_rs',
            'jenis_pendaftaran'        => 'required|in:mandiri,batch',
            'peserta'                  => 'required|array|min:1',
            'peserta.*.pegawai_id'     => 'required|exists:sumber_daya_manusia,id',
        ]);

        $status = $request->has('ajukan') ? 'diajukan_admin_unit' : 'draft';

        $pendaftaran->update([
            'ujikom_jadwal_id' => $request->ujikom_jadwal_id,
            'unit_kerja_id'    => $request->unit_kerja_id,
            'jenis_pendaftaran'=> $request->jenis_pendaftaran,
            'status'           => $status,
        ]);

        // Hapus berkas lama dari disk
        foreach ($pendaftaran->berkas as $b) {
            Storage::disk('public')->delete($b->file_path);
            $b->delete();
        }
        // Hapus peserta lama
        $pendaftaran->peserta()->delete();

        // Buat ulang peserta + berkas
        foreach ($request->peserta as $idx => $pesertaData) {
            $jabatanTujuan = isset($pesertaData['formasi_tujuan_id'])
                ? Formasijabatan::with('jenjang')->find($pesertaData['formasi_tujuan_id'])
                : null;

            UjikomPendaftaranPeserta::create([
                'ujikom_pendaftaran_id' => $pendaftaran->id,
                'pegawai_id'            => $pesertaData['pegawai_id'],
                'jabatan_tujuan_id'     => $jabatanTujuan?->id,
                'jenjang_tujuan'        => $jabatanTujuan?->jenjang?->nama_jenjang,
                'jabatan_tujuan_nama'   => $jabatanTujuan
                                            ? ($jabatanTujuan->nama_formasi . ' ' . ($jabatanTujuan->jenjang?->nama_jenjang ?? ''))
                                            : null,
                'sisa_formasi'          => $pesertaData['sisa_formasi'] ?? null,
                'status_formasi'        => $pesertaData['status_formasi'] ?? 'tidak_tersedia',
            ]);

            if ($request->hasFile("berkas.{$idx}")) {
                foreach ($request->file("berkas.{$idx}") as $persyaratanId => $file) {
                    if (!$file || !$file->isValid()) continue;
                    $persyaratan = UjikomPersyaratan::find($persyaratanId);
                    if (!$persyaratan) continue;

                    $path = $file->store('ujikom/berkas', 'public');
                    UjikomPendaftaranBerkas::create([
                        'ujikom_pendaftaran_id' => $pendaftaran->id,
                        'pegawai_id'            => $pesertaData['pegawai_id'],
                        'ujikom_persyaratan_id' => $persyaratanId,
                        'nama_berkas'           => $persyaratan->nama_syarat,
                        'file_path'             => $path,
                    ]);
                }
            }
        }

        $msg = $status === 'diajukan_admin_unit'
            ? 'Permohonan berhasil diperbarui dan diajukan.'
            : 'Permohonan berhasil diperbarui.';

        return redirect()->route('ujikom.permohonan.show', $pendaftaran->id)->with('success', $msg);
    }

    public function destroy($id)
    {
        $pendaftaran = UjikomPendaftaran::with('berkas')->findOrFail($id);
        $this->authorizeEdit($pendaftaran);

        foreach ($pendaftaran->berkas as $b) {
            Storage::disk('public')->delete($b->file_path);
        }
        $pendaftaran->delete();

        return redirect()->route('ujikom.permohonan.index')->with('success', 'Permohonan berhasil dihapus.');
    }

    // ─── Workflow ─────────────────────────────────────────────────────────────

    public function ajukan($id)
    {
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->dibuat_oleh !== Auth::id() && !Auth::user()->hasRole(['admin', 'super_admin'])) {
            abort(403);
        }

        if ($pendaftaran->status !== 'draft') {
            return back()->with('error', 'Hanya permohonan berstatus Draft yang dapat diajukan.');
        }

        if ($pendaftaran->peserta()->count() === 0) {
            return back()->with('error', 'Permohonan harus memiliki minimal 1 peserta sebelum diajukan.');
        }

        $pendaftaran->update(['status' => 'diajukan_admin_unit']);
        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan berhasil diajukan ke Admin Unit.');
    }

    public function verifikasiAdminUnit(Request $request, $id)
    {
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->status !== 'diajukan_admin_unit') {
            return back()->with('error', 'Status tidak sesuai untuk aksi ini.');
        }

        $pendaftaran->update([
            'status'              => 'diverifikasi_admin_unit',
            'catatan_admin_unit'  => $request->catatan_admin_unit,
        ]);

        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan berhasil diverifikasi oleh Admin Unit.');
    }

    public function tolakAdminUnit(Request $request, $id)
    {
        $request->validate(['catatan_admin_unit' => 'required|string']);
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->status !== 'diajukan_admin_unit') {
            return back()->with('error', 'Status tidak sesuai untuk aksi ini.');
        }

        $pendaftaran->update([
            'status'             => 'ditolak_admin_unit',
            'catatan_admin_unit' => $request->catatan_admin_unit,
        ]);

        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan ditolak oleh Admin Unit.');
    }

    public function ajukanPusbin($id)
    {
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->status !== 'diverifikasi_admin_unit') {
            return back()->with('error', 'Status tidak sesuai untuk aksi ini.');
        }

        $pendaftaran->update(['status' => 'diajukan_pusbin']);
        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan berhasil diajukan ke Pusbin.');
    }

    public function verifikasiPusbin(Request $request, $id)
    {
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->status !== 'diajukan_pusbin') {
            return back()->with('error', 'Status tidak sesuai untuk aksi ini.');
        }

        $pendaftaran->update([
            'status'          => 'selesai',
            'catatan_pusbin'  => $request->catatan_pusbin,
        ]);

        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan diverifikasi Pusbin dan dinyatakan Selesai.');
    }

    public function tolakPusbin(Request $request, $id)
    {
        $request->validate(['catatan_pusbin' => 'required|string']);
        $pendaftaran = UjikomPendaftaran::findOrFail($id);

        if ($pendaftaran->status !== 'diajukan_pusbin') {
            return back()->with('error', 'Status tidak sesuai untuk aksi ini.');
        }

        $pendaftaran->update([
            'status'         => 'ditolak_pusbin',
            'catatan_pusbin' => $request->catatan_pusbin,
        ]);

        return redirect()->route('ujikom.permohonan.show', $id)->with('success', 'Permohonan ditolak oleh Pusbin.');
    }

    public function verifikasiBerkas(Request $request, $id)
    {
        $request->validate([
            'berkas'                    => 'required|array',
            'berkas.*.id'               => 'required|exists:ujikom_pendaftaran_berkas,id',
            'berkas.*.status_verifikasi'=> 'required|in:belum,diterima,ditolak',
            'berkas.*.catatan'          => 'nullable|string',
        ]);

        foreach ($request->berkas as $item) {
            UjikomPendaftaranBerkas::where('id', $item['id'])
                ->where('ujikom_pendaftaran_id', $id)
                ->update([
                    'status_verifikasi' => $item['status_verifikasi'],
                    'catatan'           => $item['catatan'] ?? null,
                ]);
        }

        return back()->with('success', 'Status verifikasi berkas berhasil diperbarui.');
    }

    // ─── Private Auth Helpers ─────────────────────────────────────────────────

    private function authorizeView(UjikomPendaftaran $pendaftaran): void
    {
        $user = Auth::user();
        if ($user->hasRole(['admin', 'super_admin'])) return;
        if ($pendaftaran->dibuat_oleh === $user->id) return;
        abort(403);
    }

    private function authorizeEdit(UjikomPendaftaran $pendaftaran): void
    {
        if (!$pendaftaran->bisaDiedit()) {
            abort(403, 'Permohonan dengan status ini tidak dapat diedit.');
        }
        $user = Auth::user();
        if ($user->hasRole(['admin', 'super_admin'])) return;
        if ($pendaftaran->dibuat_oleh === $user->id) return;
        abort(403);
    }
}
