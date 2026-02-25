<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenjangJabatan extends Model
{
    protected $table = 'jenjang_jabatan';

    protected $fillable = [
        'nama_jenjang', 'golongan', 'kategori'
    ];

    public function getNamaJenjangAttribute(): ?string
{
    // sesuaikan fallback dgn struktur table kamu
    // prioritas kolom 'nama' / 'nama_jenjang'; kalau tidak ada, gabungkan 'kategori' + 'golongan'
    if (!empty($this->attributes['nama_jenjang'])) return $this->attributes['nama_jenjang'];
    // if (!empty($this->attributes['nama'])) return $this->attributes['nama'];
    $kat = $this->attributes['kategori'] ?? null;
    $gol = $this->attributes['golongan'] ?? null;
    return trim(($kat ? $kat : '').' '.($gol ? $gol : '')) ?: null;
}


}

