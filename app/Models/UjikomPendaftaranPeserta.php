<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomPendaftaranPeserta extends Model
{
    use HasFactory;

    protected $table = 'ujikom_pendaftaran_peserta';

    protected $fillable = [
        'ujikom_pendaftaran_id',
        'pegawai_id',
        'jabatan_tujuan_id',
        'jenjang_tujuan',
        'jabatan_tujuan_nama',
        'sisa_formasi',
        'status_formasi',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(UjikomPendaftaran::class, 'ujikom_pendaftaran_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Sdmmodels::class, 'pegawai_id');
    }

    public function jabatanTujuan()
    {
        return $this->belongsTo(Formasijabatan::class, 'jabatan_tujuan_id');
    }
}
