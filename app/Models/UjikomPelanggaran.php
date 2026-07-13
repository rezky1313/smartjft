<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UjikomPelanggaran extends Model
{
    protected $table = 'ujikom_pelanggaran';

    protected $fillable = [
        'ujikom_sesi_id',
        'jenis_pelanggaran',
        'pelanggaran_ke',
        'waktu_kejadian',
    ];

    protected $casts = [
        'waktu_kejadian' => 'datetime',
    ];

    public function sesi()
    {
        return $this->belongsTo(UjikomSesi::class, 'ujikom_sesi_id');
    }

    public function getLabelJenisAttribute(): string
    {
        return match ($this->jenis_pelanggaran) {
            'pindah_tab'  => 'Pindah Tab',
            'minimize'    => 'Minimize Jendela',
            'kamera_mati' => 'Kamera Mati/Tertutup',
            default       => $this->jenis_pelanggaran,
        };
    }
}
