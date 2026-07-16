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
use App\Models\UjikomHasil;
use App\Models\UjikomNilaiManual;
use App\Models\UjikomPelanggaran;

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
                    'selesai'     => UjikomSesi::where('ujikom_jadwal_id', $j->id)->whereIn('status_sesi', ['selesai', 'timeout', 'disubmit_paksa'])->count(),
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

            $paketAktif = PaketUjian::where('ujikom_jadwal_id', $j->id)->where('status', 'aktif')->latest()->first();
            $j->mode_sesi_taksonomi = $paketAktif && $paketAktif->mode_pemilihan === 'sesi_taksonomi';

            if (!$peserta) {
                $j->sesi_peserta = null;
                $j->sesi_teknis = null;
                $j->sesi_mansoskul = null;
                $j->sesi_dibuka = false;
                return;
            }

            if ($j->mode_sesi_taksonomi) {
                // Mode 2 Sesi CAT: tidak butuh "buka sesi" admin terpisah — paket aktif = siap dimulai
                $j->sesi_dibuka  = true;
                $j->sesi_peserta = null;
                $j->sesi_teknis  = UjikomSesi::where('ujikom_jadwal_id', $j->id)
                    ->where('peserta_id', $peserta->id)->where('jenis_sesi', 'teknis')->first();
                $j->sesi_mansoskul = $j->sesi_teknis
                    ? UjikomSesi::where('sesi_teknis_id', $j->sesi_teknis->id)->first()
                    : null;
            } else {
                $j->sesi_teknis = null;
                $j->sesi_mansoskul = null;
                $j->sesi_peserta = UjikomSesi::where('ujikom_jadwal_id', $j->id)
                    ->where('peserta_id', $peserta->id)->where('jenis_sesi', 'tunggal')->first();
                // Cek apakah sesi sudah dibuka oleh admin
                $j->sesi_dibuka = UjikomSesi::where('ujikom_jadwal_id', $j->id)->exists();
            }
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

        // 2. Ambil paket ujian aktif untuk jadwal ini (ambil yang paling baru diaktifkan jika lebih dari 1)
        $paket = PaketUjian::where('ujikom_jadwal_id', $jadwalId)
            ->where('status', 'aktif')
            ->latest()
            ->first();

        if (!$paket) {
            return back()->with('error', 'Paket ujian belum tersedia untuk jadwal ini. Silakan hubungi admin Pusbin.');
        }

        // Mode "2 Sesi CAT" (Teknis + Mansoskul terpisah) punya alur sendiri — lihat mulaiSesiTaksonomi().
        // Mode acak_otomatis/manual (satu sesi CAT tunggal) tetap pakai alur lama di bawah, TIDAK diubah.
        if ($paket->mode_pemilihan === 'sesi_taksonomi') {
            return $this->mulaiSesiTaksonomi($jadwal, $paket, $peserta);
        }

        // 3. Cek apakah sesi sudah dibuka oleh admin (ada sesi lain utk jadwal ini)
        // Atau peserta sudah punya sesi
        $sesiExisting = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('peserta_id', $peserta->id)
            ->where('jenis_sesi', 'tunggal')
            ->first();

        if ($sesiExisting) {
            if ($sesiExisting->status_sesi === 'berlangsung') {
                return redirect()->route('ujikom-online.ujian', $sesiExisting->id);
            }
            if (in_array($sesiExisting->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
                return redirect()->route('ujikom-online.hasil', $sesiExisting->id);
            }
            // status_sesi === 'menunggu' (dibuat massal oleh admin via bukaSesi(), belum ada soal/timer)
            // → lanjut ke bawah untuk diaktifkan, JANGAN buat sesi baru (unique: ujikom_jadwal_id + peserta_id + jenis_sesi)
        } else {
            // Belum ada baris ujikom_sesi sama sekali untuk peserta ini di jadwal ini — artinya
            // Admin Pusbin belum memanggil bukaSesi(). Peserta TIDAK BOLEH mulai ujian sendiri.
            return back()->with('error', 'Sesi ujian belum dibuka oleh Admin Pusbin. Silakan tunggu pengumuman lebih lanjut.');
        }

        // 4. Generate soal
        $soalSet = $paket->generateSoalUntukPeserta($peserta->id);

        if ($soalSet->isEmpty()) {
            return back()->with('error', 'Tidak ada soal yang tersedia di bank soal.');
        }

        // 5. Aktifkan sesi 'menunggu' yang sudah dibuka admin (lihat blok if/else di atas — $sesiExisting
        // dijamin terisi di titik ini, sesi baru TIDAK PERNAH dibuat langsung oleh peserta)
        $sesi = DB::transaction(function () use ($paket, $soalSet, $request, $sesiExisting) {
            $now = Carbon::now();

            $sesiExisting->update([
                'paket_ujian_id' => $paket->id,
                'status_sesi'    => 'berlangsung',
                'waktu_mulai'    => $now,
                'batas_waktu'    => $now->copy()->addMinutes($paket->durasi_menit),
                'ip_address'     => $request->ip(),
            ]);
            $sesi = $sesiExisting;

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
     * Alur "mulai" khusus paket mode sesi_taksonomi (2 Sesi CAT: Teknis lalu Mansoskul).
     * Dipanggil dari mulai() di atas — TIDAK menyentuh alur lama (mode acak_otomatis/manual).
     */
    private function mulaiSesiTaksonomi(UjikomJadwal $jadwal, PaketUjian $paket, UjikomPendaftaranPeserta $peserta)
    {
        $jadwalId = $jadwal->id;

        $sesiTeknis = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('peserta_id', $peserta->id)
            ->where('jenis_sesi', 'teknis')
            ->first();

        if (!$sesiTeknis) {
            $soal = $paket->generateSoalSesi('teknis');
            if ($soal->isEmpty()) {
                return back()->with('error', 'Soal Sesi Teknis belum tersedia di bank soal. Hubungi admin Pusbin.');
            }
            $sesiTeknis = $this->buatSesiGanda($jadwalId, $paket, $peserta, 'teknis', $soal, $paket->durasi_menit_teknis ?: 60);
            return redirect()->route('ujikom-online.ujian', $sesiTeknis->id);
        }

        if (!in_array($sesiTeknis->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
            return redirect()->route('ujikom-online.ujian', $sesiTeknis->id);
        }

        // Sesi Teknis selesai -> cek/lanjut Sesi Mansoskul
        $sesiMansoskul = UjikomSesi::where('sesi_teknis_id', $sesiTeknis->id)->first();
        if (!$sesiMansoskul) {
            $soal = $paket->generateSoalSesi('mansoskul');
            if ($soal->isEmpty()) {
                return back()->with('error', 'Soal Sesi Mansoskul belum tersedia di bank soal. Hubungi admin Pusbin.');
            }
            $sesiMansoskul = $this->buatSesiGanda($jadwalId, $paket, $peserta, 'mansoskul', $soal, $paket->durasi_menit_mansoskul ?: 60, $sesiTeknis->id);
            return redirect()->route('ujikom-online.ujian', $sesiMansoskul->id)
                ->with('info', 'Sesi 1 (Teknis) selesai. Memulai Sesi 2 (Mansoskul).');
        }

        if (!in_array($sesiMansoskul->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
            return redirect()->route('ujikom-online.ujian', $sesiMansoskul->id);
        }

        return redirect()->route('ujikom-online.hasil-gabungan', ['jadwalId' => $jadwalId, 'pesertaId' => $peserta->id]);
    }

    private function buatSesiGanda($jadwalId, PaketUjian $paket, UjikomPendaftaranPeserta $peserta, string $jenisSesi, $soalSet, int $durasiMenit, ?int $sesiTeknisId = null)
    {
        return DB::transaction(function () use ($jadwalId, $paket, $peserta, $jenisSesi, $soalSet, $durasiMenit, $sesiTeknisId) {
            $now  = Carbon::now();
            $sesi = UjikomSesi::create([
                'ujikom_jadwal_id' => $jadwalId,
                'paket_ujian_id'   => $paket->id,
                'peserta_id'       => $peserta->id,
                'jenis_sesi'       => $jenisSesi,
                'sesi_teknis_id'   => $sesiTeknisId,
                'status_sesi'      => 'berlangsung',
                'waktu_mulai'      => $now,
                'batas_waktu'      => $now->copy()->addMinutes($durasiMenit),
                'ip_address'       => request()->ip(),
            ]);

            foreach ($soalSet as $urutan => $soal) {
                UjikomSesiSoal::create([
                    'ujikom_sesi_id' => $sesi->id,
                    'bank_soal_id'   => $soal->id,
                    'urutan'         => $urutan + 1,
                ]);
            }

            UjikomSesiLog::create([
                'ujikom_sesi_id' => $sesi->id,
                'aksi'           => 'mulai',
                'detail'         => ['jumlah_soal' => $soalSet->count(), 'jenis_sesi' => $jenisSesi, 'paket' => $paket->nama],
            ]);

            return $sesi;
        });
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

        // Jika sesi sudah selesai/timeout → redirect ke hasil (sesi tunggal) atau ke index (sesi 2-sesi,
        // supaya tombol "Lanjutkan Ujian" di index yang menentukan langkah berikutnya via mulai())
        if (in_array($sesi->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
            return $sesi->jenis_sesi === 'tunggal'
                ? redirect()->route('ujikom-online.hasil', $sesi->id)
                : redirect()->route('ujikom-online.index')->with('info', 'Sesi ini sudah selesai. Silakan lanjutkan dari daftar ujian.');
        }

        // Cek timeout
        if ($sesi->batas_waktu && Carbon::now()->gte($sesi->batas_waktu)) {
            $this->selesaikanSesi($sesi, 'timeout');
            return $sesi->jenis_sesi === 'tunggal'
                ? redirect()->route('ujikom-online.hasil', $sesi->id)
                : redirect()->route('ujikom-online.index')->with('info', 'Waktu sesi habis. Silakan lanjutkan dari daftar ujian.');
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
            $this->selesaikanSesi($sesi, 'timeout');
            return response()->json(['success' => false, 'message' => 'Waktu habis.', 'timeout' => true], 422);
        }

        $request->validate([
            'soal_id'  => 'required|exists:ujikom_sesi_soal,id',
            'jawaban'  => 'required|in:A,B,C,D',
        ]);

        $soalSesi = UjikomSesiSoal::where('ujikom_sesi_id', $sesiId)
            ->where('id', $request->soal_id)
            ->firstOrFail();

        $pilihanDipilih = $soalSesi->bankSoal->pilihan
            ->first(fn($p) => strtoupper($p->kode_pilihan) === $request->jawaban);

        $update = ['pilihan_dipilih' => $request->jawaban, 'waktu_dijawab' => Carbon::now()];

        if ($sesi->jenis_sesi === 'mansoskul') {
            // Mansoskul: tiap pilihan sudah punya nilai skala 1-5 sendiri (bukan benar/salah)
            $update['nilai_diperoleh'] = $pilihanDipilih->nilai_skala ?? null;
        } else {
            $update['is_benar'] = (bool) ($pilihanDipilih->is_benar ?? false);
        }

        $soalSesi->update($update);
        // PENTING: jangan kembalikan is_benar/nilai_diperoleh ke frontend — cegah kebocoran kunci jawaban

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

        // Tentukan status akhir berdasar waktu server (bukan timer JS) — request submit ini bisa
        // datang dari tombol "Submit Ujian" (manual) MAUPUN dari auto-submit saat timer JS mencapai
        // 0 (client-side, tidak bisa dipercaya sepenuhnya). Kalau batas_waktu sudah lewat saat
        // request diterima server, ini genuine timeout, bukan submit manual.
        $statusAkhir = ($sesi->batas_waktu && Carbon::now()->gte($sesi->batas_waktu))
            ? 'timeout'
            : 'selesai';

        // Hitung nilai dan selesaikan
        $this->selesaikanSesi($sesi, $statusAkhir);

        // Log
        UjikomSesiLog::create([
            'ujikom_sesi_id' => $sesiId,
            'aksi'           => 'submit',
            'detail'         => [
                'nilai_akhir'  => $sesi->fresh()->nilai_akhir,
                'status_lulus' => $sesi->fresh()->status_lulus,
            ],
        ]);

        if ($sesi->jenis_sesi === 'teknis') {
            return redirect()->route('ujikom-online.index')
                ->with('info', 'Sesi 1 (Teknis) berhasil disubmit. Silakan lanjutkan ke Sesi 2 (Mansoskul).');
        }

        if ($sesi->jenis_sesi === 'mansoskul') {
            return redirect()->route('ujikom-online.hasil-gabungan', [
                'jadwalId'  => $sesi->ujikom_jadwal_id,
                'pesertaId' => $sesi->peserta_id,
            ])->with('success', 'Ujian berhasil disubmit.');
        }

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
        if (!in_array($sesi->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
            return redirect()->route('ujikom-online.ujian', $sesiId);
        }

        // Hitung durasi pengerjaan
        $durasi = null;
        if ($sesi->waktu_mulai && $sesi->waktu_selesai) {
            $durasi = $sesi->waktu_mulai->diff($sesi->waktu_selesai);
        }

        return view('ujikom.online.hasil', compact('sesi', 'durasi'));
    }

    /**
     * Halaman hasil gabungan (mode 2 Sesi CAT — Teknis + Mansoskul).
     */
    public function hasilGabungan($jadwalId, $pesertaId)
    {
        $peserta = UjikomPendaftaranPeserta::with('pegawai')->findOrFail($pesertaId);

        // Otorisasi: admin boleh semua, peserta cuma boleh lihat hasilnya sendiri
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'super_admin']) && $peserta->pegawai_id !== $user->sdm_id) {
            abort(403, 'Anda tidak memiliki akses ke hasil ini.');
        }

        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        $sesiTeknis = UjikomSesi::where('ujikom_jadwal_id', $jadwalId)
            ->where('peserta_id', $pesertaId)->where('jenis_sesi', 'teknis')->first();
        $sesiMansoskul = $sesiTeknis
            ? UjikomSesi::where('sesi_teknis_id', $sesiTeknis->id)->first()
            : null;

        $hasil = UjikomHasil::where('ujikom_jadwal_id', $jadwalId)->where('peserta_id', $pesertaId)->first();

        $bobotTeknis    = $jadwal->getBobotAspek('teknis');
        $bobotMansoskul = $jadwal->getBobotAspek('mansoskul');

        $nilaiManual = UjikomNilaiManual::where('ujikom_jadwal_id', $jadwalId)->where('peserta_id', $pesertaId)->get()
            ->keyBy(fn($n) => $n->kompetensi . '_' . $n->aspek);

        return view('ujikom.online.hasil_gabungan', compact(
            'jadwal', 'peserta', 'sesiTeknis', 'sesiMansoskul', 'hasil', 'bobotTeknis', 'bobotMansoskul', 'nilaiManual'
        ));
    }

    /**
     * AJAX: catat pelanggaran anti-cheat (pindah tab, minimize, kamera mati).
     */
    public function catatPelanggaran(Request $request, $sesiId)
    {
        $sesi = UjikomSesi::findOrFail($sesiId);
        $this->authorizeAksesSesi($sesi);

        $request->validate([
            'jenis_pelanggaran' => 'required|in:pindah_tab,minimize,kamera_mati',
        ]);

        if ($sesi->status_sesi !== 'berlangsung') {
            return response()->json(['pelanggaran_ke' => 0, 'auto_submit' => false, 'pesan' => '']);
        }

        $jumlahSaatIni = $sesi->pelanggaran()->count() + 1;

        UjikomPelanggaran::create([
            'ujikom_sesi_id'     => $sesiId,
            'jenis_pelanggaran'  => $request->jenis_pelanggaran,
            'pelanggaran_ke'     => $jumlahSaatIni,
            'waktu_kejadian'     => Carbon::now(),
        ]);

        // Belum ada broadcast realtime (Echo/Pusher) ke dashboard admin — admin melihatnya
        // lewat kolom "Log Pelanggaran" di monitoring.blade.php saat auto-refresh 30 detik.

        $autoSubmit = $jumlahSaatIni >= 3;
        if ($autoSubmit) {
            // 'disubmit_paksa' — bukan 'timeout', supaya tidak tertukar dengan makna "waktu habis"
            $this->selesaikanSesi($sesi, 'disubmit_paksa');
        }

        return response()->json([
            'pelanggaran_ke' => $jumlahSaatIni,
            'auto_submit'    => $autoSubmit,
            'pesan'          => $autoSubmit
                ? 'Anda telah melanggar aturan ujian 3 kali. Ujian disubmit otomatis.'
                : "Peringatan! Pelanggaran ke-{$jumlahSaatIni} dari 3. Ujian akan disubmit otomatis jika mencapai 3 kali.",
        ]);
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

        if ($paket->mode_pemilihan === 'sesi_taksonomi') {
            return back()->with('error', 'Paket mode "2 Sesi CAT" tidak perlu dibuka manual — peserta otomatis bisa mulai Sesi 1 (Teknis) begitu paket aktif.');
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
     * Form input nilai manual (Wawancara/Presentasi) untuk semua peserta terverifikasi di jadwal —
     * hanya aspek yang aktif (bobot > 0) di konfigurasi jadwal yang ditampilkan.
     */
    public function formNilaiManual($jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        $pesertaList = UjikomPendaftaranPeserta::with(['pegawai', 'pendaftaran'])
            ->whereHas('pendaftaran', function ($q) use ($jadwalId) {
                $q->where('ujikom_jadwal_id', $jadwalId)
                  ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
            })->get();

        $bobotTeknis    = $jadwal->getBobotAspek('teknis');
        $bobotMansoskul = $jadwal->getBobotAspek('mansoskul');

        $nilaiManual = UjikomNilaiManual::where('ujikom_jadwal_id', $jadwalId)->get()
            ->groupBy('peserta_id')
            ->map(fn($rows) => $rows->keyBy(fn($n) => $n->kompetensi . '_' . $n->aspek));

        return view('ujikom.online.input_nilai_manual', compact(
            'jadwal', 'pesertaList', 'bobotTeknis', 'bobotMansoskul', 'nilaiManual'
        ));
    }

    /**
     * Simpan/update satu nilai manual (Wawancara/Presentasi, skala 1-5) untuk 1 peserta.
     * Kalau sesi Mansoskul peserta itu sudah selesai, coba finalisasi ulang Hasil Ujikom-nya.
     */
    public function inputNilaiManual(Request $request)
    {
        $request->validate([
            'ujikom_jadwal_id' => 'required|exists:ujikom_jadwal,id',
            'peserta_id'       => 'required|exists:ujikom_pendaftaran_peserta,id',
            'kompetensi'       => 'required|in:teknis,mansoskul',
            'aspek'            => 'required|in:wawancara,presentasi',
            'nilai'            => 'required|integer|min:1|max:5',
            'catatan'          => 'nullable|string',
        ]);

        UjikomNilaiManual::updateOrCreate(
            [
                'ujikom_jadwal_id' => $request->ujikom_jadwal_id,
                'peserta_id'       => $request->peserta_id,
                'kompetensi'       => $request->kompetensi,
                'aspek'            => $request->aspek,
            ],
            [
                'nilai'        => $request->nilai,
                'catatan'      => $request->catatan,
                'dinilai_oleh' => Auth::id(),
            ]
        );

        // Kalau sesi Mansoskul peserta ini sudah selesai, coba finalisasi ulang (mungkin ini nilai terakhir yg ditunggu)
        $sesiMansoskul = UjikomSesi::where('ujikom_jadwal_id', $request->ujikom_jadwal_id)
            ->where('peserta_id', $request->peserta_id)
            ->where('jenis_sesi', 'mansoskul')
            ->where('status_sesi', 'selesai')
            ->first();

        if ($sesiMansoskul) {
            $this->cobaFinalisasiHasil($sesiMansoskul);
        }

        return back()->with('success', 'Nilai berhasil disimpan.');
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
            $this->selesaikanSesi($sesi, 'timeout');
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

        $sesiList = UjikomSesi::with(['peserta.pegawai', 'paketUjian', 'pelanggaran'])
            ->where('ujikom_jadwal_id', $jadwalId)
            ->get();

        // Hitung progress + ringkasan pelanggaran per sesi
        $sesiList->each(function ($s) {
            $s->progress_data       = $s->progress;
            $s->jumlah_pelanggaran  = $s->pelanggaran->count();
            $s->ringkasan_pelanggaran = $s->pelanggaran->countBy('jenis_pelanggaran');
        });

        $statistik = [
            'total'       => $sesiList->count(),
            'menunggu'    => $sesiList->where('status_sesi', 'menunggu')->count(),
            'berlangsung' => $sesiList->where('status_sesi', 'berlangsung')->count(),
            'selesai'     => $sesiList->whereIn('status_sesi', ['selesai', 'timeout', 'disubmit_paksa'])->count(),
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

        $this->selesaikanSesi($sesi, 'timeout');

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

    /**
     * Selesaikan sesi (submit/timeout), sadar jenis sesi:
     * - 'tunggal' (alur lama): pakai UjikomSesi::hitungNilai() apa adanya (sudah sync ke ujikom_hasil).
     * - 'teknis'/'mansoskul' (2 Sesi CAT): hitung nilai sesi ini saja, JANGAN sync ke ujikom_hasil
     *   langsung — kalau ini sesi Mansoskul (sesi terakhir), coba finalisasi via cobaFinalisasiHasil().
     */
    private function selesaikanSesi(UjikomSesi $sesi, string $statusAkhir = 'selesai'): void
    {
        if ($sesi->jenis_sesi === 'tunggal') {
            $sesi->hitungNilai();
            if ($statusAkhir !== 'selesai') {
                $sesi->update(['status_sesi' => $statusAkhir]);
            }
            return;
        }

        $sesi->update([
            'nilai_akhir'   => $sesi->hitungNilaiSesi(),
            'status_sesi'   => $statusAkhir,
            'waktu_selesai' => Carbon::now(),
        ]);

        if ($sesi->jenis_sesi === 'mansoskul') {
            $this->cobaFinalisasiHasil($sesi->fresh());
        }
    }

    /**
     * Coba finalisasi ke Hasil Ujikom setelah sesi Mansoskul (sesi terakhir) selesai. Jika masih
     * menunggu nilai manual (Wawancara/Presentasi aktif di jadwal tapi belum diinput), tunda dulu
     * dengan status "belum_dinilai" — dipanggil ulang dari inputNilaiManual() setiap nilai baru masuk.
     */
    private function cobaFinalisasiHasil(UjikomSesi $sesiMansoskul): void
    {
        $sesiTeknis = UjikomSesi::find($sesiMansoskul->sesi_teknis_id);
        $jadwal     = $sesiMansoskul->jadwal;
        $pesertaId  = $sesiMansoskul->peserta_id;

        if (!$sesiTeknis || !$jadwal) {
            return;
        }

        $bobotTeknis    = $jadwal->getBobotAspek('teknis');
        $bobotMansoskul = $jadwal->getBobotAspek('mansoskul');

        $ambilNilaiManual = function (string $kompetensi, string $aspek) use ($jadwal, $pesertaId) {
            return UjikomNilaiManual::where([
                'ujikom_jadwal_id' => $jadwal->id,
                'peserta_id'       => $pesertaId,
                'kompetensi'       => $kompetensi,
                'aspek'            => $aspek,
            ])->value('nilai');
        };

        $wawancaraTeknis     = $bobotTeknis['wawancara'] > 0 ? $ambilNilaiManual('teknis', 'wawancara') : null;
        $presentasiTeknis    = $bobotTeknis['presentasi'] > 0 ? $ambilNilaiManual('teknis', 'presentasi') : null;
        $wawancaraMansoskul  = $bobotMansoskul['wawancara'] > 0 ? $ambilNilaiManual('mansoskul', 'wawancara') : null;
        $presentasiMansoskul = $bobotMansoskul['presentasi'] > 0 ? $ambilNilaiManual('mansoskul', 'presentasi') : null;

        // Cek apakah semua aspek yang WAJIB (bobotnya > 0, artinya aktif di jadwal) sudah terisi
        $menungguManual =
            ($bobotTeknis['wawancara'] > 0 && $wawancaraTeknis === null) ||
            ($bobotTeknis['presentasi'] > 0 && $presentasiTeknis === null) ||
            ($bobotMansoskul['wawancara'] > 0 && $wawancaraMansoskul === null) ||
            ($bobotMansoskul['presentasi'] > 0 && $presentasiMansoskul === null);

        // Indikasi kecurangan dari pelanggaran di kedua sesi (dihitung di sini juga supaya
        // sudah terlihat di tampilan detail meski status masih "belum_dinilai")
        $totalPelanggaran = $sesiTeknis->pelanggaran()->count() + $sesiMansoskul->pelanggaran()->count();

        // Rincian mentah per aspek — disimpan di kedua cabang (belum final maupun final)
        // supaya Pusbin bisa lihat aspek mana yang sudah/belum terisi lewat tampilan detail.
        $rincianAspek = [
            'nilai_teknis_cat'           => $sesiTeknis->nilai_akhir,
            'nilai_teknis_wawancara'     => $wawancaraTeknis,
            'nilai_teknis_presentasi'    => $presentasiTeknis,
            'nilai_mansoskul_cat'        => $sesiMansoskul->nilai_akhir,
            'nilai_mansoskul_wawancara'  => $wawancaraMansoskul,
            'nilai_mansoskul_presentasi' => $presentasiMansoskul,
            'status_kecurangan'          => $totalPelanggaran >= 3 ? 'terindikasi' : 'normal',
        ];

        if ($menungguManual) {
            UjikomHasil::updateOrCreate(
                ['ujikom_jadwal_id' => $jadwal->id, 'peserta_id' => $pesertaId],
                array_merge($rincianAspek, [
                    'ujikom_sesi_id'   => $sesiMansoskul->id,
                    'jenis_ujian'      => 'online',
                    'status_kelulusan' => 'belum_dinilai',
                ])
            );
            return;
        }

        $nilaiTeknisFinal    = UjikomSesi::hitungNilaiKompetensi('teknis', (float) $sesiTeknis->nilai_akhir, $wawancaraTeknis, $presentasiTeknis, $bobotTeknis);
        $nilaiMansoskulFinal = UjikomSesi::hitungNilaiKompetensi('mansoskul', (float) $sesiMansoskul->nilai_akhir, $wawancaraMansoskul, $presentasiMansoskul, $bobotMansoskul);

        $bobotJenjang = $jadwal->getBobotJenjang();
        $nilaiAkhir   = ($nilaiTeknisFinal * $bobotJenjang['teknis'] / 100) + ($nilaiMansoskulFinal * $bobotJenjang['mansoskul'] / 100);
        $passingGrade = $sesiMansoskul->paketUjian->passing_grade ?? 70;
        $statusLulus  = $nilaiAkhir >= $passingGrade ? 'lulus' : 'tidak_lulus';

        UjikomHasil::updateOrCreate(
            ['ujikom_jadwal_id' => $jadwal->id, 'peserta_id' => $pesertaId],
            array_merge($rincianAspek, [
                'ujikom_sesi_id'   => $sesiMansoskul->id,
                'jenis_ujian'      => 'online',
                'nilai_teknis'     => $nilaiTeknisFinal,
                'nilai_mansoskul'  => $nilaiMansoskulFinal,
                'bobot_teknis'     => $bobotJenjang['teknis'],
                'bobot_mansoskul'  => $bobotJenjang['mansoskul'],
                'nilai'            => round($nilaiAkhir, 2),
                'status_kelulusan' => $statusLulus,
                'passing_grade'    => $passingGrade,
                'tanggal_ujian'    => now()->toDateString(),
            ])
        );
    }

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
