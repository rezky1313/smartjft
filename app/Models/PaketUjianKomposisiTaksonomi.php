<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketUjianKomposisiTaksonomi extends Model
{
    protected $table = 'paket_ujian_komposisi_taksonomi';

    protected $fillable = [
        'paket_ujian_id',
        'jenis_sesi',
        'taksonomi',
        'jumlah_soal',
    ];

    public function paketUjian()
    {
        return $this->belongsTo(PaketUjian::class, 'paket_ujian_id');
    }

    public function getLabelTaksonomiAttribute(): string
    {
        return match ($this->taksonomi) {
            'C1_mengingat'    => 'C1 — Mengingat',
            'C2_memahami'     => 'C2 — Memahami',
            'C3_menerapkan'   => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencipta'     => 'C6 — Mencipta',
            default           => $this->taksonomi,
        };
    }
}
