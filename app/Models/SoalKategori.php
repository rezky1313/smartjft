<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoalKategori extends Model
{
    use HasFactory;

    protected $table = 'soal_kategori';

    protected $fillable = [
        'nama',
        'jabatan',
        'jenjang',
        'matra',
        'klasifikasi',
        'bidang',
        'deskripsi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function soal()
    {
        return $this->hasMany(BankSoal::class, 'soal_kategori_id');
    }

    // ─── Scope ───────────────────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelJenjangAttribute(): string
    {
        return match ($this->jenjang) {
            'pemula'       => 'Pemula',
            'terampil'     => 'Terampil',
            'mahir'        => 'Mahir',
            'penyelia'     => 'Penyelia',
            'ahli_pertama' => 'Ahli Pertama',
            'ahli_muda'    => 'Ahli Muda',
            'ahli_madya'   => 'Ahli Madya',
            'ahli_utama'   => 'Ahli Utama',
            'umum'         => 'Umum',
            default        => $this->jenjang,
        };
    }

    public function getLabelMatraAttribute(): string
    {
        return match ($this->matra) {
            'darat'          => 'Darat',
            'laut'           => 'Laut',
            'udara'          => 'Udara',
            'asdp'           => 'ASDP',
            'perkeretaapian' => 'Perkeretaapian',
            'umum'           => 'Umum',
            default          => $this->matra,
        };
    }

    public function getLabelKlasifikasiAttribute(): string
    {
        return match ($this->klasifikasi) {
            'keahlian'     => 'Keahlian',
            'keterampilan' => 'Keterampilan',
            'umum'         => 'Umum',
            default        => $this->klasifikasi,
        };
    }
}
