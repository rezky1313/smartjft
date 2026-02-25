<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formasijabatan extends Model
{
    use HasFactory;

    protected $table = 'formasijabatan'; // pastikan nama tabel sesuai di database

    protected $fillable = [
        'nama_formasi',
        'jenjang',
        'unit_kerja_id',
        'kuota',
        'tahun_formasi',
    ];

    public function unitkerja()
    {
        return $this->belongsTo(Rumahsakit::class, 'unit_kerja_id');
    }
}
