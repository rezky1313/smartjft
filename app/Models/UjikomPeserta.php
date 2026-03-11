<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjikomPeserta extends Model
{
    use SoftDeletes;

    protected $table = 'ujikom_peserta';

    protected $fillable = [
        'ujikom_permohonan_id',
        'pegawai_id',
        'hasil',
        'catatan_hasil',
    ];

    protected $casts = [
        'hasil' => 'string',
    ];

    /**
     * Relasi ke Permohonan
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(UjikomPermohonan::class, 'ujikom_permohonan_id');
    }

    /**
     * Relasi ke Pegawai (SDM)
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Sdmmodels::class, 'pegawai_id');
    }

    /**
     * Scope untuk filter berdasarkan hasil
     */
    public function scopeWithHasil($query, $hasil)
    {
        if ($hasil) {
            return $query->where('hasil', $hasil);
        }
        return $query;
    }

    /**
     * Get hasil badge color
     */
    public function getHasilBadgeColorAttribute(): string
    {
        return match($this->hasil) {
            'lulus' => 'success',
            'tidak_lulus' => 'danger',
            'belum' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get hasil label
     */
    public function getHasilLabelAttribute(): string
    {
        return match($this->hasil) {
            'lulus' => 'Lulus',
            'tidak_lulus' => 'Tidak Lulus',
            'belum' => 'Belum',
            default => 'Unknown',
        };
    }
}
