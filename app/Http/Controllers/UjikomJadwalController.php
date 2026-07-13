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
            'jenis_ujian'                        => 'nullable|in:kenaikan_jabatan,perpindahan_jabatan,penyesuaian_inpassing',
            'matra'                              => 'nullable|string|max:100',
            'deskripsi'                          => 'nullable|string',
            'tanggal_mulai'                      => 'required|date',
            'tanggal_selesai'                    => 'required|date|after_or_equal:tanggal_mulai',
            'tempat'                             => 'required|string|max:255',
            'kuota'                              => 'required|integer|min:1',
            'jenjang_tujuan'                      => 'required|in:ahli_utama,ahli_madya,ahli_muda,ahli_pertama,penyelia,mahir,terampil,pemula',
            'persyaratan'                        => 'nullable|array',
            'persyaratan.*.nama_syarat'          => 'required_with:persyaratan|string|max:500',
            'persyaratan.*.keterangan'           => 'nullable|string',
            'persyaratan.*.urutan'               => 'nullable|integer|min:1',
            'persyaratan.*.file_contoh'          => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:5120',
        ]);

        $status = $request->has('publish') ? 'published' : 'draft';

        $jadwal = UjikomJadwal::create([
            'judul'                      => $request->judul,
            'jenis_ujian'                => $request->jenis_ujian,
            'matra'                      => $request->matra,
            'deskripsi'                  => $request->deskripsi,
            'tanggal_mulai'              => $request->tanggal_mulai,
            'tanggal_selesai'            => $request->tanggal_selesai,
            'tempat'                     => $request->tempat,
            'kuota'                      => $request->kuota,
            'status'                     => $status,
            'dibuat_oleh'                => Auth::id(),
            'jenjang_tujuan'             => $request->jenjang_tujuan,
            'teknis_wawancara_aktif'     => $request->boolean('teknis_wawancara_aktif'),
            'teknis_presentasi_aktif'    => $request->boolean('teknis_presentasi_aktif'),
            'mansoskul_wawancara_aktif'  => $request->boolean('mansoskul_wawancara_aktif'),
            'mansoskul_presentasi_aktif' => $request->boolean('mansoskul_presentasi_aktif'),
        ]);

        // Jika user mengirimkan persyaratan dari form, gunakan itu.
        // Jika tidak, insert persyaratan default otomatis.
        if ($request->has('persyaratan') && !empty(array_filter(array_column($request->persyaratan ?? [], 'nama_syarat')))) {
            $this->simpanPersyaratan($request, $jadwal->id);
        } else {
            $this->insertPersyaratanDefault($jadwal->id);
        }

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
        $pendaftaranList = UjikomPendaftaran::with(['peserta.pegawai.formasi.jenjang', 'peserta.jabatanTujuan', 'unitKerja'])
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
            'jenis_ujian'                        => 'nullable|in:kenaikan_jabatan,perpindahan_jabatan,penyesuaian_inpassing',
            'matra'                              => 'nullable|string|max:100',
            'deskripsi'                          => 'nullable|string',
            'tanggal_mulai'                      => 'required|date',
            'tanggal_selesai'                    => 'required|date|after_or_equal:tanggal_mulai',
            'tempat'                             => 'required|string|max:255',
            'kuota'                              => 'required|integer|min:1',
            'jenjang_tujuan'                      => 'required|in:ahli_utama,ahli_madya,ahli_muda,ahli_pertama,penyelia,mahir,terampil,pemula',
            'persyaratan'                        => 'nullable|array',
            'persyaratan.*.nama_syarat'          => 'required_with:persyaratan|string|max:500',
            'persyaratan.*.keterangan'           => 'nullable|string',
            'persyaratan.*.urutan'               => 'nullable|integer|min:1',
            'persyaratan.*.file_contoh'          => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:5120',
        ]);

        $status = $request->has('publish') ? 'published' : 'draft';

        $jadwal->update([
            'judul'                      => $request->judul,
            'jenis_ujian'                => $request->jenis_ujian,
            'matra'                      => $request->matra,
            'deskripsi'                  => $request->deskripsi,
            'tanggal_mulai'              => $request->tanggal_mulai,
            'tanggal_selesai'            => $request->tanggal_selesai,
            'tempat'                     => $request->tempat,
            'kuota'                      => $request->kuota,
            'status'                     => $status,
            'jenjang_tujuan'             => $request->jenjang_tujuan,
            'teknis_wawancara_aktif'     => $request->boolean('teknis_wawancara_aktif'),
            'teknis_presentasi_aktif'    => $request->boolean('teknis_presentasi_aktif'),
            'mansoskul_wawancara_aktif'  => $request->boolean('mansoskul_wawancara_aktif'),
            'mansoskul_presentasi_aktif' => $request->boolean('mansoskul_presentasi_aktif'),
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

        // Cek apakah ada pendaftaran yang sudah dalam proses verifikasi atau selesai
        $statusTerverifikasi = ['diverifikasi_admin_unit', 'diajukan_pusbin', 'diverifikasi_pusbin', 'selesai'];
        $adaPendaftarTerproses = UjikomPendaftaran::where('ujikom_jadwal_id', $id)
            ->whereIn('status', $statusTerverifikasi)
            ->exists();

        if ($adaPendaftarTerproses) {
            $jumlah = UjikomPendaftaran::where('ujikom_jadwal_id', $id)
                ->whereIn('status', $statusTerverifikasi)
                ->count();
            return redirect()->back()
                ->with('error', "Jadwal tidak dapat dihapus karena terdapat {$jumlah} pendaftaran yang sudah diverifikasi atau sedang diproses. Selesaikan atau tolak pendaftaran tersebut terlebih dahulu.");
        }

        // Hapus pendaftaran yang masih draft/ditolak beserta berkas & pesertanya
        $pendaftaranList = UjikomPendaftaran::where('ujikom_jadwal_id', $id)->get();
        foreach ($pendaftaranList as $pend) {
            foreach ($pend->berkas as $berkas) {
                Storage::disk('public')->delete($berkas->file_path);
            }
            $pend->berkas()->delete();
            $pend->peserta()->delete();
            $pend->delete();
        }

        // Hapus persyaratan beserta file contohnya
        foreach ($jadwal->persyaratan as $p) {
            if ($p->file_contoh) {
                Storage::disk('public')->delete($p->file_contoh);
            }
            $p->delete();
        }

        $jadwal->delete();
        return redirect()->route('ujikom.jadwal.index')->with('success', 'Jadwal uji kompetensi berhasil dihapus.');
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

    private function insertPersyaratanDefault(int $jadwalId): void
    {
        $defaults = [
            ['nama_syarat' => 'Surat Usulan Uji Kompetensi dari Pimpinan Unit Kerja', 'urutan' => 1],
            ['nama_syarat' => 'Surat Keterangan Integritas dan Moralitas', 'urutan' => 2],
            ['nama_syarat' => 'Surat Keterangan Sehat dari Dokter', 'urutan' => 3],
            ['nama_syarat' => 'SK PNS', 'urutan' => 4],
            ['nama_syarat' => 'SK Kenaikan Pangkat Terakhir', 'urutan' => 5],
            ['nama_syarat' => 'SK Jabatan Terakhir', 'urutan' => 6],
            ['nama_syarat' => 'Ijazah Terakhir', 'urutan' => 7],
            ['nama_syarat' => 'Dokumen Evaluasi Kinerja Minimal Predikat BAIK 2 (dua) Tahun Terakhir', 'urutan' => 8],
            ['nama_syarat' => 'PAK Kumulatif', 'urutan' => 9],
            ['nama_syarat' => 'Surat Keterangan Tidak Sedang Menjalani Tugas Belajar (lebih dari 6 bulan dan diberhentikan dari jabatan) dan Tidak Sedang Cuti Diluar Tanggungan Negara', 'urutan' => 10],
            ['nama_syarat' => 'Sertifikat Kompetensi Pelatihan yang Dipersyaratkan untuk Menduduki Jabatan', 'urutan' => 11],
            ['nama_syarat' => 'Karya Tulis Ilmiah (bagi usulan ke Jenjang Ahli Madya)', 'urutan' => 12],
        ];

        foreach ($defaults as $syarat) {
            UjikomPersyaratan::create([
                'ujikom_jadwal_id' => $jadwalId,
                'nama_syarat'      => $syarat['nama_syarat'],
                'urutan'           => $syarat['urutan'],
                'keterangan'       => null,
                'file_contoh'      => null,
            ]);
        }
    }

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
