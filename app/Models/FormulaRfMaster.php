<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaRfMaster extends Model
{
    protected $table = 'formula_rf_master';

    protected $fillable = [
        'kode_jf',
        'jenjang',
        'unsur',
        'sub_unsur',
        'butir_kegiatan',
        'satuan_hasil',
        'angka_kredit',
        'waktu_menit',
        'sumber_volume',
        'volume_konstanta',
        'urutan',
    ];

    protected $casts = [
        'angka_kredit' => 'decimal:5',
        'waktu_menit' => 'decimal:2',
        'volume_konstanta' => 'decimal:2',
        'urutan' => 'integer',
    ];
}
