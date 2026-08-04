<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiHasil extends Model
{
    protected $table = 'rekomendasi_formasi_hasil';

    protected $fillable = [
        'usulan_id',
        'jenjang',
        'total_wpv',
        'kebutuhan_raw',
        'kebutuhan_bulat',
        'bezetting',
        'formasi_sistem',
        'formasi_final',
        'usulan_admin_unit',
    ];

    protected $casts = [
        'total_wpv' => 'decimal:4',
        'kebutuhan_raw' => 'decimal:4',
    ];

    public function usulan()
    {
        return $this->belongsTo(RekomendasiFormasiUsulan::class, 'usulan_id');
    }
}
