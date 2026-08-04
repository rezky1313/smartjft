<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiPegawaiExisting extends Model
{
    protected $table = 'rekomendasi_formasi_pegawai_existing';

    protected $fillable = [
        'usulan_id',
        'sdm_id',
        'nama',
        'nip',
        'jenjang',
    ];

    public function usulan()
    {
        return $this->belongsTo(RekomendasiFormasiUsulan::class, 'usulan_id');
    }

    public function sdm()
    {
        return $this->belongsTo(Sdmmodels::class, 'sdm_id');
    }
}
