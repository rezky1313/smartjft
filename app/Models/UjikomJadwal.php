<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomJadwal extends Model
{
    use HasFactory;

    protected $table = 'ujikom_jadwal';

    protected $fillable = [
        'judul',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'tempat',
        'kuota',
        'status',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function persyaratan()
    {
        return $this->hasMany(UjikomPersyaratan::class, 'ujikom_jadwal_id')->orderBy('urutan');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'published' => 'Dipublikasikan',
            'selesai'   => 'Selesai',
            default     => '-',
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'published' => 'success',
            'selesai'   => 'dark',
            default     => 'secondary',
        };
    }
}
