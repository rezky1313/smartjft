<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengangkatanPermohonan;
use App\Models\PengangkatanKandidat;
use App\Models\PengangkatanSurat;
use App\Models\UnitKerja;
use App\Models\Formasijabatan;
use App\Models\UjikomHasil;
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
            ->withCount('kandidat');

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
            'total'        => (clone $baseStats)->count(),
            'diajukan'     => (clone $baseStats)->where('status', 'diajukan')->count(),
            'menunggu_ttd' => (clone $baseStats)->where('status', 'menunggu_ttd')->count(),
            'selesai'      => (clone $baseStats)->where('status', 'selesai')->count(),
        ];

        $unitKerjaList = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja']);

        return view('pengangkatan.index', compact('permohonan', 'stats', 'unitKerjaList'));
    }

    /**
     * Form buat permohonan baru.
     */
    public function create()
    {
        $user = auth()->user();
        $unitKerja = $user->hasRole('admin_unit')
            ? UnitKerja::where('id', $user->unit_kerja_id)->first()
            : null;

        $unitKerjaList = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja']);

        return view('pengangkatan.create', compact('unitKerja', 'unitKerjaList'));
    }

    /**
     * AJAX: daftar pegawai lulus ujikom di suatu unit kerja yang belum pernah diusulkan
     * (dipakai oleh pencarian "tambah peserta" di form create/edit).
     */
    public function getPesertaLulus(Request $request)
    {
        $request->validate(['unit_kerja_id' => 'required|integer']);

        $sudahDiajukan = PengangkatanKandidat::whereHas(
                'permohonan',
                fn($q) => $q->where('status', '!=', 'ditolak')
            )
            ->when($request->filled('kecuali_permohonan_id'), function ($q) use ($request) {
                // saat edit, jangan kecualikan kandidat milik permohonan yang sedang diedit sendiri
                $q->where('pengangkatan_permohonan_id', '!=', $request->kecuali_permohonan_id);
            })
            ->pluck('ujikom_hasil_id')
            ->filter()
            ->all();

        $hasil = UjikomHasil::with(['peserta.pegawai', 'peserta.jabatanTujuan.jenjang'])
            ->where('status_kelulusan', 'lulus')
            ->whereHas('peserta.pendaftaran', fn($q) => $q->where('unit_kerja_id', $request->unit_kerja_id))
            ->whereNotIn('id', $sudahDiajukan)
            ->get();

        $data = $hasil->map(function ($h) {
            $peserta = $h->peserta;
            $pegawai = $peserta?->pegawai;
            $jabatanTujuan = $peserta?->jabatanTujuan;

            return [
                'ujikom_hasil_id'   => $h->id,
                'pegawai_id'        => $pegawai?->id,
                'nama'              => $pegawai?->nama_lengkap,
                'nip'               => $pegawai?->nip,
                'jabatan_tujuan_id' => $jabatanTujuan?->id,
                'jabatan_tujuan'    => $jabatanTujuan?->nama_formasi,
                'jenjang_tujuan'    => $peserta?->jenjang_tujuan,
                'nilai'             => $h->nilai,
            ];
        })->filter(fn($x) => $x['pegawai_id'] && $x['jabatan_tujuan_id'])->values();

        return response()->json($data);
    }

    /**
     * AJAX: validasi realtime sisa formasi untuk satu jabatan tujuan saat peserta ditambahkan di form.
     */
    public function validasiFormasiPeserta(Request $request)
    {
        $request->validate([
            'jabatan_tujuan_id' => 'required|integer',
        ]);

        $formasi = Formasijabatan::find($request->jabatan_tujuan_id);
        $sisaFormasi = $formasi ? $formasi->sisa : 0;

        $sudahDiajukan = (int) ($request->jumlah_sudah_diajukan_jabatan_sama ?? 0);

        if (($sudahDiajukan + 1) > $sisaFormasi) {
            return response()->json([
                'valid' => false,
                'pesan' => "Formasi tidak mencukupi. Sisa formasi jabatan ini: {$sisaFormasi}, sudah diajukan: {$sudahDiajukan}.",
            ]);
        }

        return response()->json(['valid' => true, 'sisa_formasi' => $sisaFormasi]);
    }

    /**
     * Simpan permohonan baru + peserta yang diusulkan (langsung direkomendasikan, tanpa ranking).
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit_kerja_id'               => 'required|integer|exists:unit_kerja,id',
            'tanggal_permohonan'          => 'required|date',
            'file_surat_permohonan'       => 'nullable|file|mimes:pdf|max:5120',
            'peserta'                     => 'required|array|min:1',
            'peserta.*.ujikom_hasil_id'   => 'required|integer|exists:ujikom_hasil,id',
            'peserta.*.pegawai_id'        => 'required|integer|exists:sumber_daya_manusia,id',
            'peserta.*.jabatan_tujuan_id' => 'required|integer|exists:formasi_jabatan,id',
            'peserta.*.jenjang_tujuan'    => 'required|string',
        ], [
            'peserta.required' => 'Tambahkan minimal satu peserta yang diusulkan.',
        ]);

        if ($pesan = $this->validasiPesertaGabungan($request->peserta)) {
            return back()->withInput()->withErrors(['peserta' => $pesan]);
        }

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

        $this->simpanPeserta($permohonan, $request->peserta);

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
     * Validasi gabungan sebelum simpan: sisa formasi per jabatan tujuan + pastikan tidak ada
     * peserta yang ujikom_hasil_id-nya sudah dipakai di permohonan lain yang masih berjalan.
     * Kembalikan pesan error (string) jika ada pelanggaran, null jika lolos semua.
     */
    private function validasiPesertaGabungan(array $daftarPeserta, ?int $kecualiPermohonanId = null): ?string
    {
        // 1. Sisa formasi per jabatan tujuan
        $perJabatan = collect($daftarPeserta)->groupBy('jabatan_tujuan_id');
        foreach ($perJabatan as $jabatanTujuanId => $daftar) {
            $formasi = Formasijabatan::find($jabatanTujuanId);
            $sisaFormasi = $formasi ? $formasi->sisa : 0;

            if ($daftar->count() > $sisaFormasi) {
                return "Jumlah usulan untuk jabatan \"{$formasi?->nama_formasi}\" ({$daftar->count()} orang) melebihi sisa formasi yang tersedia ({$sisaFormasi}). Kurangi jumlah usulan atau ajukan formasi tambahan terlebih dahulu.";
            }
        }

        // 2. Tidak boleh ada ujikom_hasil_id yang sudah dipakai di permohonan lain (non-ditolak)
        $ujikomHasilIds = collect($daftarPeserta)->pluck('ujikom_hasil_id')->all();
        $sudahDipakai = PengangkatanKandidat::whereIn('ujikom_hasil_id', $ujikomHasilIds)
            ->whereHas('permohonan', fn($q) => $q->where('status', '!=', 'ditolak'))
            ->when($kecualiPermohonanId, fn($q) => $q->where('pengangkatan_permohonan_id', '!=', $kecualiPermohonanId))
            ->with('pegawai')
            ->get();

        if ($sudahDipakai->isNotEmpty()) {
            $nama = $sudahDipakai->pluck('pegawai.nama_lengkap')->filter()->unique()->implode(', ');
            return "Peserta berikut sudah diusulkan di permohonan lain yang masih berjalan: {$nama}.";
        }

        return null;
    }

    /**
     * Simpan baris PengangkatanKandidat dari array peserta hasil form (semua otomatis direkomendasikan).
     */
    private function simpanPeserta(PengangkatanPermohonan $permohonan, array $daftarPeserta): void
    {
        foreach ($daftarPeserta as $p) {
            $hasil = UjikomHasil::with('peserta.pegawai.formasiJabatan.jenjang')->find($p['ujikom_hasil_id']);
            $pegawai = $hasil?->peserta?->pegawai;
            $formasi = Formasijabatan::find($p['jabatan_tujuan_id']);

            PengangkatanKandidat::create([
                'pengangkatan_permohonan_id' => $permohonan->id,
                'pegawai_id'                 => $p['pegawai_id'],
                'ujikom_hasil_id'            => $p['ujikom_hasil_id'],
                'jabatan_asal'               => $pegawai?->formasiJabatan?->nama_formasi ?? '-',
                'jenjang_asal'               => $pegawai?->formasiJabatan?->jenjang?->nama_jenjang ?? '-',
                'jabatan_tujuan_id'          => $p['jabatan_tujuan_id'],
                'jenjang_tujuan'             => $p['jenjang_tujuan'],
                'nilai_ujikom'               => $hasil?->nilai,
                'ranking'                    => null,
                'formasi_tersedia'           => $formasi?->sisa,
                'status_kandidat'            => 'direkomendasikan',
            ]);
        }
    }

    /**
     * Detail permohonan + daftar peserta yang diusulkan.
     */
    public function show($id)
    {
        $permohonan = PengangkatanPermohonan::with([
            'unitKerja', 'pengaju', 'surat',
            'kandidat' => fn($q) => $q->orderBy('jabatan_tujuan_id'),
            'kandidat.pegawai',
            'kandidat.jabatanTujuan.jenjang',
        ])->findOrFail($id);

        // Group peserta per jabatan tujuan
        $kandidatPerJabatan = $permohonan->kandidat->groupBy('jabatan_tujuan_id');

        return view('pengangkatan.show', compact('permohonan', 'kandidatPerJabatan'));
    }

    /**
     * Form edit (hanya jika draft).
     */
    public function edit($id)
    {
        $permohonan = PengangkatanPermohonan::with('kandidat.pegawai', 'kandidat.jabatanTujuan')->findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403, 'Hanya permohonan draft yang bisa diedit.');

        $unitKerjaList = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja']);

        return view('pengangkatan.create', compact('permohonan', 'unitKerjaList'));
    }

    /**
     * Update permohonan (data dasar + sinkronisasi ulang daftar peserta).
     */
    public function update(Request $request, $id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'draft', 403);

        $request->validate([
            'unit_kerja_id'               => 'required|integer|exists:unit_kerja,id',
            'tanggal_permohonan'          => 'required|date',
            'file_surat_permohonan'       => 'nullable|file|mimes:pdf|max:5120',
            'peserta'                     => 'required|array|min:1',
            'peserta.*.ujikom_hasil_id'   => 'required|integer|exists:ujikom_hasil,id',
            'peserta.*.pegawai_id'        => 'required|integer|exists:sumber_daya_manusia,id',
            'peserta.*.jabatan_tujuan_id' => 'required|integer|exists:formasi_jabatan,id',
            'peserta.*.jenjang_tujuan'    => 'required|string',
        ], [
            'peserta.required' => 'Tambahkan minimal satu peserta yang diusulkan.',
        ]);

        if ($pesan = $this->validasiPesertaGabungan($request->peserta, $permohonan->id)) {
            return back()->withInput()->withErrors(['peserta' => $pesan]);
        }

        $data = $request->only(['unit_kerja_id', 'tanggal_permohonan']);

        if ($request->hasFile('file_surat_permohonan')) {
            $data['file_surat_permohonan'] = $request->file('file_surat_permohonan')
                ->store('pengangkatan/surat-permohonan', 'public');
        }

        $permohonan->update($data);

        // Sinkronisasi ulang daftar peserta (hapus lama, simpan yang baru dari form)
        $permohonan->kandidat()->delete();
        $this->simpanPeserta($permohonan, $request->peserta);

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
     * Admin Pusbin tolak permohonan (sebelum surat dibuat).
     */
    public function tolak(Request $request, $id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'diajukan', 403);

        $request->validate(['catatan_pusbin' => 'required|string|max:1000']);

        $permohonan->update([
            'status'         => 'ditolak',
            'catatan_pusbin' => $request->catatan_pusbin,
        ]);

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Permohonan ditolak.');
    }

    /**
     * Admin Pusbin generate PDF surat rekomendasi: diajukan → menunggu_ttd.
     * Berisi SEMUA peserta yang diusulkan (semua otomatis berstatus direkomendasikan).
     */
    public function generateSurat($id)
    {
        $permohonan = PengangkatanPermohonan::with([
            'unitKerja', 'surat',
            'kandidat' => fn($q) => $q->orderBy('jabatan_tujuan_id'),
            'kandidat.pegawai',
            'kandidat.jabatanTujuan.jenjang',
        ])->findOrFail($id);

        abort_unless(in_array($permohonan->status, ['diajukan', 'menunggu_ttd']), 403);

        if ($permohonan->status === 'diajukan') {
            $permohonan->update(['status' => 'menunggu_ttd']);
        }

        if (!$permohonan->surat) {
            PengangkatanSurat::create([
                'pengangkatan_permohonan_id' => $permohonan->id,
                'tanggal_surat'              => now()->toDateString(),
            ]);
        }

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
     * Konfirmasi surat sudah ditandatangani: menunggu_ttd → selesai.
     */
    public function konfirmasiTtd($id)
    {
        $permohonan = PengangkatanPermohonan::findOrFail($id);
        abort_unless($permohonan->status === 'menunggu_ttd', 403);

        if ($permohonan->surat) {
            $permohonan->surat->update(['ditandatangani' => true]);
        }

        // selesaikan() lama tetap dipakai: update data formasi & TMT pegawai
        $permohonan->selesaikan();

        return redirect()->route('pengangkatan.show', $id)
            ->with('success', 'Surat dikonfirmasi dan pengangkatan diselesaikan. Data formasi pegawai telah diperbarui.');
    }
}
