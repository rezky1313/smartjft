<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiDiklat extends Model
{
    protected $table = 'pegawai_diklat';

    protected $fillable = [
        'sdm_id',
        'nama_diklat',
        'penyelenggara',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis_diklat',
        'path_sertifikat',
        'input_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function sdm()
    {
        return $this->belongsTo(Sdmmodels::class, 'sdm_id');
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }
}
