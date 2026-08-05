<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkrAmbangBatasJenjang extends Model
{
    protected $table = 'pkr_ambang_batas_jenjang';

    protected $fillable = [
        'kategori',
        'dari_jenjang',
        'ke_jenjang',
        'ak_kumulatif_minimal',
    ];
}
