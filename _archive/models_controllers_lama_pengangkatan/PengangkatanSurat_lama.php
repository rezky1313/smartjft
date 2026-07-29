<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengangkatanSurat extends Model
{
    use SoftDeletes;

    protected $table = 'pengangkatan_surat';

    protected $fillable = [
        'pengangkatan_permohonan_id',
        'nomor_surat',
        'file_path',
        'dibuat_oleh',
        'tanggal_dibuat',
    ];

    protected $casts = [
        'tanggal_dibuat' => 'datetime',
    ];

    /**
     * Relasi ke Permohonan
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PengangkatanPermohonan::class, 'pengangkatan_permohonan_id');
    }

    /**
     * Relasi ke User yang membuat surat
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Scope untuk mendapatkan surat pertimbangan terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('tanggal_dibuat', 'desc');
    }
}
