<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketUjianSoal extends Model
{
    use HasFactory;

    protected $table = 'paket_ujian_soal';

    protected $fillable = [
        'paket_ujian_id',
        'bank_soal_id',
        'urutan',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function paketUjian()
    {
        return $this->belongsTo(PaketUjian::class, 'paket_ujian_id');
    }

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }
}
