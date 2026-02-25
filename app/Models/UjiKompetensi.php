<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ujikompetensi extends Model
{

     use SoftDeletes;

    protected $table = 'uji_kompetensi';
    protected $fillable = [
        'sdm_id','kompetensi','nilai','tanggal_uji','nomor_sertifikat','keterangan'
    ];

    public function sdm()
    {
        return $this->belongsTo(Sdmmodels::class, 'sdm_id');
    }
}
