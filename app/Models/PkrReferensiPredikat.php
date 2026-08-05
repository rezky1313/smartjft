<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkrReferensiPredikat extends Model
{
    protected $table = 'pkr_referensi_predikat';

    protected $fillable = [
        'predikat',
        'persentase',
    ];
}
