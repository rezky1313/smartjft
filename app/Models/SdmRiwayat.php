<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdmRiwayat extends Model
{
    protected $table = 'sdm_riwayat';
   protected $fillable = ['sdm_id','jenjang_id','formasi_jabatan_id','tmt_mulai','tmt_selesai','reason'];

    public function sdm(){ return $this->belongsTo(Sdmmodels::class); }
}
