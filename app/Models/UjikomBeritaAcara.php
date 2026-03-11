<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjikomBeritaAcara extends Model
{
    use SoftDeletes;

    protected $table = 'ujikom_berita_acara';

    protected $fillable = [
        'ujikom_permohonan_id',
        'jenis',
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
        return $this->belongsTo(UjikomPermohonan::class, 'ujikom_permohonan_id');
    }

    /**
     * Relasi ke User yang membuat BA
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Scope untuk filter berdasarkan jenis
     */
    public function scopeWithJenis($query, $jenis)
    {
        if ($jenis) {
            return $query->where('jenis', $jenis);
        }
        return $query;
    }

    /**
     * Get jenis label
     */
    public function getJenisLabelAttribute(): string
    {
        return match($this->jenis) {
            'verifikasi' => 'Berita Acara Verifikasi',
            'hasil' => 'Berita Acara Hasil',
            default => 'Unknown',
        };
    }

    /**
     * Get URL file untuk download
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
