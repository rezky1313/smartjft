<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomPersyaratan extends Model
{
    use HasFactory;

    protected $table = 'ujikom_persyaratan';

    protected $fillable = [
        'ujikom_jadwal_id',
        'nama_syarat',
        'keterangan',
        'file_contoh',
        'urutan',
    ];

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }
}
