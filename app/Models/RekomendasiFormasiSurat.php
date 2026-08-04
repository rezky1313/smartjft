<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiSurat extends Model
{
    protected $table = 'rekomendasi_formasi_surat';

    protected $fillable = [
        'usulan_id',
        'nomor_surat',
        'tanggal_surat',
        'ditandatangani',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'ditandatangani' => 'boolean',
    ];

    public function usulan()
    {
        return $this->belongsTo(RekomendasiFormasiUsulan::class, 'usulan_id');
    }
}
