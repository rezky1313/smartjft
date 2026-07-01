<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankSoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bank_soal';

    protected $fillable = [
        'soal_kategori_id',
        'pertanyaan',
        'pembahasan',
        'tingkat_kesulitan',
        'taksonomi_bloom',
        'jenis',
        'status',
        'dibuat_oleh',
        'disetujui_oleh',
        'tanggal_disetujui',
    ];

    protected $casts = [
        'tanggal_disetujui' => 'datetime',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function kategori()
    {
        return $this->belongsTo(SoalKategori::class, 'soal_kategori_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function pilihan()
    {
        return $this->hasMany(BankSoalPilihan::class, 'bank_soal_id')->orderBy('kode_pilihan');
    }

    public function jawabanBenar()
    {
        return $this->hasOne(BankSoalPilihan::class, 'bank_soal_id')->where('is_benar', true);
    }

    // ─── Scope ───────────────────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeUmum($query)
    {
        return $query->where('jenis', 'umum');
    }

    public function scopeSpesifik($query, int $kategoriId)
    {
        return $query->where('jenis', 'spesifik')->where('soal_kategori_id', $kategoriId);
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelTingkatAttribute(): string
    {
        return match ($this->tingkat_kesulitan) {
            'mudah'  => 'Mudah',
            'sedang' => 'Sedang',
            'sulit'  => 'Sulit',
            default  => $this->tingkat_kesulitan,
        };
    }

    public function getLabelTaksonomiAttribute(): string
    {
        return match ($this->taksonomi_bloom) {
            'C1_mengingat'    => 'C1 — Mengingat',
            'C2_memahami'     => 'C2 — Memahami',
            'C3_menerapkan'   => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencipta'     => 'C6 — Mencipta',
            default           => $this->taksonomi_bloom,
        };
    }

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'draft'    => 'Draft',
            default    => $this->status,
        };
    }

    public function getBadgeTingkatAttribute(): string
    {
        return match ($this->tingkat_kesulitan) {
            'mudah'  => 'success',
            'sedang' => 'warning',
            'sulit'  => 'danger',
            default  => 'secondary',
        };
    }
}
