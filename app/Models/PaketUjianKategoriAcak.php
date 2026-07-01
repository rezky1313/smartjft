<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketUjianKategoriAcak extends Model
{
    use HasFactory;

    protected $table = 'paket_ujian_kategori_acak';

    protected $fillable = [
        'paket_ujian_id',
        'soal_kategori_id',
        'jenis_soal',
        'jumlah_soal',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function paketUjian()
    {
        return $this->belongsTo(PaketUjian::class, 'paket_ujian_id');
    }

    public function kategori()
    {
        return $this->belongsTo(SoalKategori::class, 'soal_kategori_id');
    }
}
