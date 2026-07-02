<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\UjikomHasil;
use App\Models\UjikomJadwal;
use App\Models\UjikomPendaftaranPeserta;
use App\Exports\UjikomHasilExcelExport;

class UjikomHasilController extends Controller
{
    /**
     * Rekap hasil semua jadwal ujikom.
     */
    public function index(Request $request)
    {
        $query = UjikomJadwal::whereHas('hasilUjikom')
            ->orWhereHas('pendaftaran', function ($q) {
                $q->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
            });

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_mulai', $request->tahun);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_ujian', $request->jenis);
        }

        $jadwals = $query->orderByDesc('tanggal_mulai')->get();

        // Hitung statistik per jadwal
        $jadwals->each(function ($j) {
            $hasil = UjikomHasil::where('ujikom_jadwal_id', $j->id);
            $j->stat_hasil = [
                'total'         => (clone $hasil)->count(),
                'lulus'         => (clone $hasil)->where('status_kelulusan', 'lulus')->count(),
                'tidak_lulus'   => (clone $hasil)->where('status_kelulusan', 'tidak_lulus')->count(),
                'belum_dinilai' => (clone $hasil)->where('status_kelulusan', 'belum_dinilai')->count(),
                'ada_online'    => (clone $hasil)->where('jenis_ujian', 'online')->exists(),
                'ada_offline'   => (clone $hasil)->where('jenis_ujian', 'offline')->exists(),
            ];
        });

        // Statistik global
        $statistik = [
            'total_jadwal'    => $jadwals->count(),
            'total_peserta'   => UjikomHasil::count(),
            'lulus'           => UjikomHasil::where('status_kelulusan', 'lulus')->count(),
            'tidak_lulus'     => UjikomHasil::where('status_kelulusan', 'tidak_lulus')->count(),
            'belum_dinilai'   => UjikomHasil::where('status_kelulusan', 'belum_dinilai')->count(),
        ];

        $tahunList = UjikomJadwal::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()->orderByDesc('tahun')->pluck('tahun');

        return view('ujikom.hasil.index', compact('jadwals', 'statistik', 'tahunList'));
    }

    /**
     * Detail hasil per jadwal + daftar semua peserta.
     */
    public function show($jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        // Semua peserta terverifikasi untuk jadwal ini
        $pesertaList = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->with(['pegawai.unitKerja', 'jabatanTujuan'])->get();

        // Map hasil berdasar peserta_id
        $hasilMap = UjikomHasil::where('ujikom_jadwal_id', $jadwalId)
            ->with('penilai')
            ->get()
            ->keyBy('peserta_id');

        // Statistik
        $totalPeserta   = $pesertaList->count();
        $hasilList      = $hasilMap->values();
        $lulus          = $hasilList->where('status_kelulusan', 'lulus')->count();
        $tidakLulus     = $hasilList->where('status_kelulusan', 'tidak_lulus')->count();
        $belumDinilai   = $totalPeserta - $hasilList->whereIn('status_kelulusan', ['lulus', 'tidak_lulus'])->count();

        // Distribusi nilai untuk bar chart (0-60, 61-70, 71-80, 81-90, 91-100)
        $nilaiList = $hasilList->whereNotNull('nilai')->pluck('nilai');
        $distribusiNilai = [
            '0-60'   => $nilaiList->filter(fn($v) => $v <= 60)->count(),
            '61-70'  => $nilaiList->filter(fn($v) => $v > 60 && $v <= 70)->count(),
            '71-80'  => $nilaiList->filter(fn($v) => $v > 70 && $v <= 80)->count(),
            '81-90'  => $nilaiList->filter(fn($v) => $v > 80 && $v <= 90)->count(),
            '91-100' => $nilaiList->filter(fn($v) => $v > 90)->count(),
        ];

        return view('ujikom.hasil.show', compact(
            'jadwal', 'pesertaList', 'hasilMap',
            'totalPeserta', 'lulus', 'tidakLulus', 'belumDinilai',
            'distribusiNilai'
        ));
    }

    /**
     * Form input hasil offline per peserta.
     */
    public function inputOffline($jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        // Peserta belum punya nilai (belum_dinilai atau belum ada record)
        $pesertaList = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->with(['pegawai', 'jabatanTujuan'])->get();

        $hasilMap = UjikomHasil::where('ujikom_jadwal_id', $jadwalId)
            ->get()
            ->keyBy('peserta_id');

        // Filter hanya yang belum dinilai (tidak punya record atau belum_dinilai, dan bukan online selesai)
        $pesertaBelumDinilai = $pesertaList->filter(function ($p) use ($hasilMap) {
            $h = $hasilMap->get($p->id);
            return !$h || $h->status_kelulusan === 'belum_dinilai';
        });

        // Ambil passing grade dari paket ujian aktif jadwal ini
        $paket = \App\Models\PaketUjian::where('ujikom_jadwal_id', $jadwalId)
            ->where('status', 'aktif')->first();

        return view('ujikom.hasil.input_offline', compact('jadwal', 'pesertaBelumDinilai', 'hasilMap', 'paket'));
    }

    /**
     * Simpan hasil offline.
     */
    public function simpanOffline(Request $request, $jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        $request->validate([
            'hasil'                    => 'required|array',
            'hasil.*.peserta_id'       => 'required|exists:ujikom_pendaftaran_peserta,id',
            'hasil.*.nilai'            => 'nullable|numeric|min:0|max:100',
            'hasil.*.status_kelulusan' => 'required|in:lulus,tidak_lulus,belum_dinilai',
            'hasil.*.tanggal_ujian'    => 'nullable|date',
            'hasil.*.catatan_admin'    => 'nullable|string',
        ]);

        $paket = \App\Models\PaketUjian::where('ujikom_jadwal_id', $jadwalId)
            ->where('status', 'aktif')->first();

        foreach ($request->hasil as $row) {
            if (empty($row['nilai']) && $row['status_kelulusan'] === 'belum_dinilai') {
                continue; // Skip baris kosong
            }

            UjikomHasil::updateOrCreate(
                [
                    'ujikom_jadwal_id' => $jadwalId,
                    'peserta_id'       => $row['peserta_id'],
                ],
                [
                    'jenis_ujian'      => 'offline',
                    'nilai'            => $row['nilai'] ?? null,
                    'status_kelulusan' => $row['status_kelulusan'],
                    'passing_grade'    => $paket?->passing_grade ?? null,
                    'tanggal_ujian'    => $row['tanggal_ujian'] ?? null,
                    'catatan_admin'    => $row['catatan_admin'] ?? null,
                    'dinilai_oleh'     => Auth::id(),
                ]
            );
        }

        return redirect()->route('ujikom.hasil.show', $jadwalId)
            ->with('success', 'Hasil ujian offline berhasil disimpan.');
    }

    /**
     * AJAX: update catatan admin per peserta.
     */
    public function updateCatatan(Request $request, $hasilId)
    {
        $request->validate(['catatan_admin' => 'nullable|string|max:1000']);

        $hasil = UjikomHasil::findOrFail($hasilId);
        $hasil->update(['catatan_admin' => $request->catatan_admin]);

        return response()->json(['success' => true]);
    }

    /**
     * Export rekap hasil ke PDF.
     */
    public function exportPdf($jadwalId)
    {
        $jadwal = UjikomJadwal::findOrFail($jadwalId);

        $pesertaList = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwalId) {
            $q->where('ujikom_jadwal_id', $jadwalId)
              ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
        })->with(['pegawai.unitKerja', 'jabatanTujuan'])->get();

        $hasilMap = UjikomHasil::where('ujikom_jadwal_id', $jadwalId)
            ->get()->keyBy('peserta_id');

        $lulus      = $hasilMap->where('status_kelulusan', 'lulus')->count();
        $tidakLulus = $hasilMap->where('status_kelulusan', 'tidak_lulus')->count();
        $total      = $pesertaList->count();

        $pdf = Pdf::loadView('ujikom.hasil.pdf.rekap', compact(
            'jadwal', 'pesertaList', 'hasilMap', 'lulus', 'tidakLulus', 'total'
        ))->setPaper('a4', 'landscape');

        $namaFile = 'Rekap-Hasil-Ujikom-' . str_replace(' ', '-', $jadwal->judul) . '.pdf';
        return $pdf->download($namaFile);
    }

    /**
     * Export rekap hasil ke Excel.
     */
    public function exportExcel($jadwalId)
    {
        $jadwal  = UjikomJadwal::findOrFail($jadwalId);
        $namaFile = 'Rekap-Hasil-Ujikom-' . str_replace(' ', '-', $jadwal->judul) . '.xlsx';

        return Excel::download(
            new UjikomHasilExcelExport($jadwalId, $jadwal->judul),
            $namaFile
        );
    }

    /**
     * Riwayat ujikom milik peserta (pemangku).
     */
    public function riwayatPeserta()
    {
        $user  = Auth::user();
        $sdmId = $user->sdm_id;

        $riwayat = collect();

        if ($sdmId) {
            // Cari semua peserta record milik user ini
            $pesertaIds = UjikomPendaftaranPeserta::where('pegawai_id', $sdmId)->pluck('id');

            $riwayat = UjikomHasil::whereIn('peserta_id', $pesertaIds)
                ->with(['jadwal', 'peserta'])
                ->orderByDesc('tanggal_ujian')
                ->get();
        }

        return view('ujikom.hasil.riwayat', compact('riwayat'));
    }
}
