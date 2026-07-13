<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSoalPilihan extends Model
{
    use HasFactory;

    protected $table = 'bank_soal_pilihan';

    protected $fillable = [
        'bank_soal_id',
        'kode_pilihan',
        'teks_pilihan',
        'is_benar',
        'nilai_skala',
    ];

    protected $casts = [
        'is_benar'    => 'boolean',
        'nilai_skala' => 'integer',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function soal()
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }
}
