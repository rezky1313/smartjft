<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkrReferensiKoefisien extends Model
{
    protected $table = 'pkr_referensi_koefisien';

    protected $fillable = [
        'jenjang',
        'koefisien_tahunan',
    ];
}
