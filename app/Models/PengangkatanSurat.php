<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengangkatanSurat extends Model
{
    use HasFactory;

    protected $table = 'pengangkatan_surat';

    protected $fillable = [
        'pengangkatan_permohonan_id',
        'nomor_surat',
        'file_surat',
        'tanggal_surat',
        'ditandatangani',
    ];

    protected $casts = [
        'tanggal_surat'   => 'date',
        'ditandatangani'  => 'boolean',
    ];

    // ─── Relasi ───

    public function permohonan()
    {
        return $this->belongsTo(PengangkatanPermohonan::class, 'pengangkatan_permohonan_id');
    }
}
