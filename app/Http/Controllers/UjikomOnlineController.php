<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\UjikomSesi;
use App\Models\UjikomSesiSoal;
use App\Models\UjikomSesiLog;
use App\Models\UjikomJadwal;
use App\Models\UjikomPendaftaran;
use App\Models\UjikomPendaftaranPeserta;
use App\Models\PaketUjian;

class UjikomOnlineController extends Controller
{
    // ═════════════════════════════════════════════════════════════════════════
    //  PESERTA
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Daftar jadwal ujikom yang tersedia untuk peserta / admin.
     */
    public function index()
    {
        $user = Auth::user();

        // Admin: lihat semua jadwal published/selesai
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            $jadwals = UjikomJadwal::whereIn('status', ['published', 'selesai'])
                ->orderByDesc('tanggal_mulai')
                ->get();

            // Hitung statistik sesi per jadwal
            $jadwals->each(function ($j) {
                $j->stat_sesi = [
                    'total'       => UjikomSesi::where('ujikom_jadwal_id', $j->id)->count(),
                    'menunggu'    => UjikomSesi::where('ujikom_jadwal_id', $j->id)->where('status_sesi', 'menunggu')->count(),
                    'berlangsung' => UjikomSesi::where('ujikom_jadwal_id', $j->id)->where('status_sesi', 'berlangsung')->count(),
                    'selesai'     => UjikomSesi::where('ujikom_jadwal_id', $j->id)->whereIn('status_sesi', ['selesai', 'timeout'])->count(),
                ];
                // Cek apakah sudah ada sesi dibuka
                $j->sesi_dibuka = UjikomSesi::where('ujikom_jadwal_id', $j->id)->exists();
            });

            return view('ujikom.online.index', compact('jadwals'));
        }

        // Peserta (pemangku): cari jadwal di mana peserta sudah terdaftar & diverifikasi
        $sdmId = $user->sdm_id;

        $pesertaRecords = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) {
            $q->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->where('pegawai_id', $sdmId)
          ->with([
              'pendaftaran.jadwal',
          ])->get();

        // Group by jadwal
        $jadwalIds = $pesertaRecords->pluck('pendaftaran.ujikom_jadwal_id')->unique();
        $jadwals   = UjikomJadwal::whereIn('id', $jadwalIds)
            ->whereIn('status', ['published', 'selesai'])
            ->orderByDesc('tanggal_mulai')
            ->get();

        // Untuk setiap jadwal, cek sesi peserta
        $jadwals->each(function ($j) use ($pesertaRecords) {
            $peserta = $pesertaRecords->first(fn($p) => $p->pendaftaran->ujikom_jadwal_id === $j->id);
            $j->peserta_record = $peserta;
            $j->sesi_peserta   = $peserta
                ? UjikomSesi::where('ujikom_jadwal_id', $j->id)->where('peserta_id', $peserta->id)->first()
                : null;
            // Cek apakah sesi sudah dibuka oleh admin
            $j->sesi_dibuka = UjikomSesi::where('ujikom_jadwal_id', $j->id)->exists();
        });

        return view('ujikom.online.index', compact('jadwals'));
    }

    /**
     * Peserta klik "Mulai Ujian".
     */
    public function mulai(Request $request, $jadwalId)
    {
        $user    = Auth::user();
        $sdmId   = $user->sdm_id;
        $jadwal  = UjikomJadwal::findOrFail($jadwalId);

        // 1. Cari peserta terdaftar & diverifikasi di jadwal ini
        $peserta = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->where('pegawai_id', $sdmId)->first();

        if (!$peserta) {
            return back()->with('error', 'Anda tidak terdaftar atau belum diverifikasi untuk jadwal ini.');
        }

        // 2. Cek apakah sesi sudah dibuka oleh admin (ada sesi lain utk jadwal ini)
        // Atau peserta sudah punya sesi
        $sesiExisting = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('peserta_id', $peserta->id)
            ->first();

        if ($sesiExisting) {
            if ($sesiExisting->status_sesi === 'berlangsung') {
                return redirect()->route('ujikom-online.ujian', $sesiExisting->id);
            }
            if (in_array($sesiExisting->status_sesi, ['selesai', 'timeout'])) {
                return redirect()->route('ujikom-online.hasil', $sesiExisting->id);
            }
            // status_sesi === 'menunggu' (dibuat massal oleh admin via bukaSesi(), belum ada soal/timer)
            // → lanjut ke bawah untuk diaktifkan, JANGAN buat sesi baru (unique: ujikom_jadwal_id + peserta_id)
        }

        // 3. Ambil paket ujian aktif untuk jadwal ini (ambil yang paling baru diaktifkan jika lebih dari 1)
        $paket = PaketUjian::where('ujikom_jadwal_id', $jadwalId)
            ->where('status', 'aktif')
            ->latest()
            ->first();

        if (!$paket) {
            return back()->with('error', 'Paket ujian belum tersedia untuk jadwal ini. Silakan hubungi admin Pusbin.');
        }

        // 4. Generate soal
        $soalSet = $paket->generateSoalUntukPeserta($peserta->id);

        if ($soalSet->isEmpty()) {
            return back()->with('error', 'Tidak ada soal yang tersedia di bank soal.');
        }

        // 5. Aktifkan sesi 'menunggu' yang sudah ada (dibuat admin), atau buat baru jika belum ada sama sekali
        $sesi = DB::transaction(function () use ($jadwalId, $paket, $peserta, $soalSet, $request, $sesiExisting) {
            $now  = Carbon::now();
            $data = [
                'paket_ujian_id' => $paket->id,
                'status_sesi'    => 'berlangsung',
                'waktu_mulai'    => $now,
                'batas_waktu'    => $now->copy()->addMinutes($paket->durasi_menit),
                'ip_address'     => $request->ip(),
            ];

            if ($sesiExisting) {
                $sesiExisting->update($data);
                $sesi = $sesiExisting;
            } else {
                $sesi = UjikomSesi::create($data + [
                    'ujikom_jadwal_id' => $jadwalId,
                    'peserta_id'       => $peserta->id,
                ]);
            }

            // 6. Insert soal dengan urutan
            foreach ($soalSet as $urutan => $soal) {
                UjikomSesiSoal::create([
                    'ujikom_sesi_id' => $sesi->id,
                    'bank_soal_id'   => $soal->id,
                    'urutan'         => $urutan + 1,
                ]);
            }

            // 7. Log mulai
            UjikomSesiLog::create([
                'ujikom_sesi_id' => $sesi->id,
                'aksi'           => 'mulai',
                'detail'         => ['jumlah_soal' => $soalSet->count(), 'paket' => $paket->nama],
            ]);

            return $sesi;
        });

        return redirect()->route('ujikom-online.ujian', $sesi->id);
    }

    /**
     * Halaman ujian CAT-style.
     */
    public function ujian($sesiId)
    {
        $sesi = UjikomSesi::with(['soal.bankSoal.pilihan', 'jadwal', 'paketUjian', 'peserta.pegawai'])
            ->findOrFail($sesiId);

        // Cek kepemilikan
        $this->authorizeAksesSesi($sesi);

        // Jika sesi sudah selesai/timeout → redirect ke hasil
        if (in_array($sesi->status_sesi, ['selesai', 'timeout'])) {
            return redirect()->route('ujikom-online.hasil', $sesi->id);
        }

        // Cek timeout
        if ($sesi->batas_waktu && Carbon::now()->gte($sesi->batas_waktu)) {
            $sesi->hitungNilai();
            $sesi->update(['status_sesi' => 'timeout']);
            return redirect()->route('ujikom-online.hasil', $sesi->id);
        }

        // Siapkan data soal (acak pilihan jika paket mengatur)
        $daftarSoal = $sesi->soal->map(function ($ss) use ($sesi) {
            $pilihan = $ss->bankSoal->pilihan;
            if ($sesi->paketUjian->acak_pilihan) {
                $pilihan = $pilihan->shuffle();
            }
            return [
                'id'               => $ss->id,
                'urutan'           => $ss->urutan,
                'pertanyaan'       => $ss->bankSoal->pertanyaan,
                'pilihan'          => $pilihan->map(fn($p) => [
                    'kode' => strtoupper($p->kode_pilihan),
                    'teks' => $p->teks_pilihan,
                ]),
                'pilihan_dipilih'  => $ss->pilihan_dipilih,
            ];
        });

        $sisaWaktu = $sesi->sisa_waktu;

        return view('ujikom.online.ujian', compact('sesi', 'daftarSoal', 'sisaWaktu'));
    }

    /**
     * AJAX: simpan jawaban soal.
     */
    public function jawab(Request $request, $sesiId)
    {
        $sesi = UjikomSesi::findOrFail($sesiId);
        $this->authorizeAksesSesi($sesi);

        // Cek sesi masih berlangsung
        if ($sesi->status_sesi !== 'berlangsung') {
            return response()->json(['success' => false, 'message' => 'Sesi sudah berakhir.'], 422);
        }

        // Cek timeout
        if ($sesi->batas_waktu && Carbon::now()->gte($sesi->batas_waktu)) {
            $sesi->hitungNilai();
            $sesi->update(['status_sesi' => 'timeout']);
            return response()->json(['success' => false, 'message' => 'Waktu habis.', 'timeout' => true], 422);
        }

        $request->validate([
            'soal_id'  => 'required|exists:ujikom_sesi_soal,id',
            'jawaban'  => 'required|in:A,B,C,D',
        ]);

        $soalSesi = UjikomSesiSoal::where('ujikom_sesi_id', $sesiId)
            ->where('id', $request->soal_id)
            ->firstOrFail();

        // Tentukan benar/salah
        $jawabanBenar = $soalSesi->bankSoal->pilihan
            ->where('is_benar', true)
            ->first();

        $isBenar = $jawabanBenar && strtoupper($jawabanBenar->kode_pilihan) === $request->jawaban;

        $soalSesi->update([
            'pilihan_dipilih' => $request->jawaban,
            'is_benar'        => $isBenar,
            'waktu_dijawab'   => Carbon::now(),
        ]);

        // Log
        UjikomSesiLog::create([
            'ujikom_sesi_id' => $sesiId,
            'aksi'           => 'jawab',
            'detail'         => [
                'soal_urutan' => $soalSesi->urutan,
                'pilihan'     => $request->jawaban,
            ],
        ]);

        // Return tanpa memberitahu benar/salah
        return response()->json(['success' => true]);
    }

    /**
     * AJAX: catat navigasi antar soal.
     */
    public function navigasi(Request $request, $sesiId)
    {
        $sesi = UjikomSesi::findOrFail($sesiId);
        $this->authorizeAksesSesi($sesi);

        UjikomSesiLog::create([
            'ujikom_sesi_id' => $sesiId,
            'aksi'           => 'navigasi',
            'detail'         => [
                'dari_soal' => $request->dari,
                'ke_soal'   => $request->ke,
            ],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Peserta submit ujian.
     */
    public function submit(Request $request, $sesiId)
    {
        $sesi = UjikomSesi::findOrFail($sesiId);
        $this->authorizeAksesSesi($sesi);

        if ($sesi->status_sesi !== 'berlangsung') {
            return back()->with('error', 'Sesi sudah berakhir.');
        }

        // Hitung nilai dan selesaikan
        $sesi->hitungNilai();

        // Log
        UjikomSesiLog::create([
            'ujikom_sesi_id' => $sesiId,
            'aksi'           => 'submit',
            'detail'         => [
                'nilai_akhir'  => $sesi->fresh()->nilai_akhir,
                'status_lulus' => $sesi->fresh()->status_lulus,
            ],
        ]);

        return redirect()->route('ujikom-online.hasil', $sesiId)
            ->with('success', 'Ujian berhasil disubmit.');
    }

    /**
     * Halaman hasil ujian.
     */
    public function hasil($sesiId)
    {
        $sesi = UjikomSesi::with(['jadwal', 'paketUjian', 'peserta.pegawai'])
            ->findOrFail($sesiId);

        $this->authorizeAksesSesi($sesi);

        // Pastikan sesi sudah selesai
        if (!in_array($sesi->status_sesi, ['selesai', 'timeout'])) {
            return redirect()->route('ujikom-online.ujian', $sesiId);
        }

        // Hitung durasi pengerjaan
        $durasi = null;
        if ($sesi->waktu_mulai && $sesi->waktu_selesai) {
            $durasi = $sesi->waktu_mulai->diff($sesi->waktu_selesai);
        }

        return view('ujikom.online.hasil', compact('sesi', 'durasi'));
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ADMIN
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Admin buka sesi ujian untuk semua peserta terdaftar di jadwal.
     */
    public function bukaSesi(Request $request, $jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        // Cek paket aktif (ambil yang paling baru diaktifkan jika lebih dari 1)
        $paket = PaketUjian::where('ujikom_jadwal_id', $jadwalId)
            ->where('status', 'aktif')
            ->latest()
            ->first();

        if (!$paket) {
            return back()->with('error', 'Belum ada paket ujian aktif untuk jadwal ini.');
        }

        // Ambil semua peserta yang sudah diverifikasi pusbin
        $pesertaIds = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->pluck('id');

        if ($pesertaIds->isEmpty()) {
            return back()->with('error', 'Tidak ada peserta yang sudah diverifikasi untuk jadwal ini.');
        }

        // Buat sesi menunggu untuk setiap peserta yang belum punya sesi
        $created = 0;
        foreach ($pesertaIds as $pesertaId) {
            $exists = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
                ->where('peserta_id', $pesertaId)
                ->exists();

            if (!$exists) {
                UjikomSesi::create([
                    'ujikom_jadwal_id' => $jadwalId,
                    'paket_ujian_id'   => $paket->id,
                    'peserta_id'       => $pesertaId,
                    'status_sesi'      => 'menunggu',
                ]);
                $created++;
            }
        }

        return back()->with('success', "Sesi ujian dibuka. {$created} peserta siap mengikuti ujian.");
    }

    /**
     * Admin tutup sesi — force submit semua sesi yang masih berlangsung.
     */
    public function tutupSesi(Request $request, $jadwalId)
    {
        $sesiAktif = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('status_sesi', 'berlangsung')
            ->get();

        foreach ($sesiAktif as $sesi) {
            $sesi->hitungNilai();
            UjikomSesiLog::create([
                'ujikom_sesi_id' => $sesi->id,
                'aksi'           => 'timeout',
                'detail'         => ['alasan' => 'Admin menutup sesi'],
            ]);
        }

        // Update sesi menunggu → timeout
        UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('status_sesi', 'menunggu')
            ->update([
                'status_sesi'   => 'timeout',
                'waktu_selesai' => Carbon::now(),
            ]);

        return back()->with('success', 'Sesi ujian ditutup. Semua sesi aktif telah disubmit otomatis.');
    }

    /**
     * Admin monitoring real-time progress.
     */
    public function monitoring($jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        $sesiList = UjikomSesi::with(['peserta.pegawai', 'paketUjian'])
            ->where('ujikom_jadwal_id', $jadwalId)
            ->get();

        // Hitung progress per sesi
        $sesiList->each(function ($s) {
            $s->progress_data = $s->progress;
        });

        $statistik = [
            'total'       => $sesiList->count(),
            'menunggu'    => $sesiList->where('status_sesi', 'menunggu')->count(),
            'berlangsung' => $sesiList->where('status_sesi', 'berlangsung')->count(),
            'selesai'     => $sesiList->whereIn('status_sesi', ['selesai', 'timeout'])->count(),
        ];

        return view('ujikom.online.monitoring', compact('jadwal', 'sesiList', 'statistik'));
    }

    /**
     * Admin force submit peserta tertentu.
     */
    public function forceSubmit($sesiId)
    {
        $sesi = UjikomSesi::findOrFail($sesiId);

        if (!in_array($sesi->status_sesi, ['berlangsung', 'menunggu'])) {
            return back()->with('error', 'Sesi sudah selesai.');
        }

        $sesi->hitungNilai();

        UjikomSesiLog::create([
            'ujikom_sesi_id' => $sesi->id,
            'aksi'           => 'timeout',
            'detail'         => ['alasan' => 'Force submit oleh admin', 'admin' => Auth::user()->name],
        ]);

        return back()->with('success', 'Sesi peserta berhasil di-submit paksa.');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  HELPER
    // ═════════════════════════════════════════════════════════════════════════

    private function authorizeAksesSesi(UjikomSesi $sesi): void
    {
        $user = Auth::user();

        // Admin boleh akses semua
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return;
        }

        // Peserta hanya bisa akses sesi miliknya
        $sdmId   = $user->sdm_id;
        $pegawai = $sesi->peserta?->pegawai_id;

        if ($pegawai !== $sdmId) {
            abort(403, 'Anda tidak memiliki akses ke sesi ini.');
        }
    }
}
