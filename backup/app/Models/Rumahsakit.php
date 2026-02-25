<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rumahsakit extends Model
{
    protected $primaryKey = 'no_rs';
    protected $fillable = [
        'nama_rumahsakit',
        'alamat',
        'no_telp',
        'jam_kerja',
        'fasilitas',
        'latitude',
        'longitude',
    ];

}
