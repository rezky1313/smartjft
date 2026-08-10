<?php

namespace App\Http\Controllers;

use App\Models\UjikomJadwal;
use App\Models\UjikomNilaiManual;
use App\Models\UjikomPendaftaranPeserta;

class PenilaiController extends Controller
{
    /**
     * UJ-ROLE: landing khusus Pewawancara/Penguji — daftar jadwal yang masih
     * punya slot nilai Wawancara/Presentasi kosong. TANPA scoping/assignment,
     * siapa saja dengan role ini melihat semua jadwal yang pending.
     */
    public function index()
    {
        $jadwals = UjikomJadwal::where(function ($q) {
                $q->where('teknis_wawancara_aktif', true)
                  ->orWhere('teknis_presentasi_aktif', true)
                  ->orWhere('mansoskul_wawancara_aktif', true)
                  ->orWhere('mansoskul_presentasi_aktif', true);
            })
            ->orderByDesc('tanggal_mulai')
            ->get();

        $pending = collect();

        foreach ($jadwals as $jadwal) {
            $jumlahPeserta = UjikomPendaftaranPeserta::whereHas('pendaftaran', function ($q) use ($jadwal) {
                $q->where('ujikom_jadwal_id', $jadwal->id)
                  ->whereIn('status', ['diverifikasi_pusbin', 'selesai']);
            })->count();

            $aspekAktif = (int) $jadwal->teknis_wawancara_aktif
                + (int) $jadwal->teknis_presentasi_aktif
                + (int) $jadwal->mansoskul_wawancara_aktif
                + (int) $jadwal->mansoskul_presentasi_aktif;

            $slotDibutuhkan = $jumlahPeserta * $aspekAktif;
            $slotTerisi     = UjikomNilaiManual::where('ujikom_jadwal_id', $jadwal->id)->count();
            $slotKosong     = max(0, $slotDibutuhkan - $slotTerisi);

            if ($slotKosong > 0) {
                $pending->push([
                    'jadwal'          => $jadwal,
                    'jumlah_peserta'  => $jumlahPeserta,
                    'slot_dibutuhkan' => $slotDibutuhkan,
                    'slot_terisi'     => $slotTerisi,
                    'slot_kosong'     => $slotKosong,
                ]);
            }
        }

        return view('penilai.index', compact('pending'));
    }
}
