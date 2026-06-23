<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomPendaftaranBerkas extends Model
{
    use HasFactory;

    protected $table = 'ujikom_pendaftaran_berkas';

    protected $fillable = [
        'ujikom_pendaftaran_id',
        'pegawai_id',
        'ujikom_persyaratan_id',
        'nama_berkas',
        'file_path',
        'status_verifikasi',
        'catatan',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(UjikomPendaftaran::class, 'ujikom_pendaftaran_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Sdmmodels::class, 'pegawai_id');
    }

    public function persyaratan()
    {
        return $this->belongsTo(UjikomPersyaratan::class, 'ujikom_persyaratan_id');
    }
}
