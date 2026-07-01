<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomSesiSoal extends Model
{
    use HasFactory;

    protected $table = 'ujikom_sesi_soal';

    protected $fillable = [
        'ujikom_sesi_id',
        'bank_soal_id',
        'urutan',
        'pilihan_dipilih',
        'is_benar',
        'waktu_dijawab',
    ];

    protected $casts = [
        'is_benar'      => 'boolean',
        'waktu_dijawab' => 'datetime',
    ];

    protected $with = ['bankSoal.pilihan'];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function sesi()
    {
        return $this->belongsTo(UjikomSesi::class, 'ujikom_sesi_id');
    }

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }
}
