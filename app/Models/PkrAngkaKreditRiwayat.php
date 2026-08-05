<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkrAngkaKreditRiwayat extends Model
{
    protected $table = 'pkr_angka_kredit_riwayat';

    protected $fillable = [
        'sdm_id',
        'tahun',
        'periode_bulan',
        'jumlah_bulan',
        'predikat_kinerja',
        'persentase_predikat',
        'koefisien_tahunan',
        'angka_kredit_diperoleh',
        'jenjang_saat_itu',
        'catatan',
        'dinilai_oleh',
    ];

    public function sdm()
    {
        return $this->belongsTo(Sdmmodels::class, 'sdm_id');
    }

    public function penilai()
    {
        return $this->belongsTo(\App\Models\User::class, 'dinilai_oleh');
    }

    public static function hitungAK(int $jumlahBulan, float $persentase, float $koefisien): float
    {
        return round(($jumlahBulan / 12) * ($persentase / 100) * $koefisien, 4);
    }
}
