<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormasiJabatanHistori extends Model
{
    protected $table = 'formasi_jabatan_histori';
    public $timestamps = false;

    protected $fillable = [
        'formasi_id','unit_kerja_id','tahun_formasi','nama_formasi',
        'jenjang_id','kuota','terisi','snapshot_at',
    ];

    public function unitKerja()
    {
        return $this->belongsTo(\App\Models\Rumahsakit::class, 'unit_kerja_id', 'no_rs');
    }

    public function jenjang()
    {
        return $this->belongsTo(\App\Models\Jenjangjabatan::class, 'jenjang_id');
    }
}
