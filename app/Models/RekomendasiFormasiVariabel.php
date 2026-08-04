<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiVariabel extends Model
{
    protected $table = 'rekomendasi_formasi_variabel';

    protected $fillable = [
        'usulan_id',
        'jumlah_kbwu',
        'uji_pertama',
        'uji_reguler',
        'numpang_uji_masuk',
        'numpang_uji_keluar',
        'mutasi_masuk',
        'mutasi_keluar',
        'bbm_bensin',
        'bbm_solar',
        'hari_kerja',
    ];

    public function usulan()
    {
        return $this->belongsTo(RekomendasiFormasiUsulan::class, 'usulan_id');
    }

    /**
     * kb_diuji_total = uji_pertama + uji_reguler + numpang_uji_masuk + mutasi_masuk
     * (persis formula E4=F4+G4+O4+Q4 di Excel referensi -- lihat catatan validasi RF-1B)
     */
    public function getKbDiujiTotalAttribute(): int
    {
        return (int) $this->uji_pertama + (int) $this->uji_reguler
            + (int) $this->numpang_uji_masuk + (int) $this->mutasi_masuk;
    }
}
