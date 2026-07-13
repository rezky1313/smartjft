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
        'matra',
        'pertanyaan',
        'pembahasan',
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

    /**
     * Soal Mansoskul (dulu "Umum"). Nama method dipertahankan "umum" karena masih
     * dipanggil dari PaketUjian::generateSoalUntukPeserta()/cekKetersediaanSoal()
     * untuk konfigurasi paket ujian berjenis_soal="umum" — lihat catatan di sana.
     */
    public function scopeUmum($query)
    {
        return $query->where('jenis', 'mansoskul');
    }

    public function scopeSpesifik($query, int $kategoriId)
    {
        return $query->where('jenis', 'teknis')->where('soal_kategori_id', $kategoriId);
    }

    public function scopeMansoskul($query)
    {
        return $query->where('jenis', 'mansoskul');
    }

    public function scopeTeknis($query, ?int $kategoriId = null)
    {
        return $query->where('jenis', 'teknis')->when($kategoriId, fn($q) => $q->where('soal_kategori_id', $kategoriId));
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelJenisAttribute(): string
    {
        return match ($this->jenis) {
            'mansoskul' => 'Mansoskul',
            'teknis'    => 'Teknis',
            default     => $this->jenis,
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
            default          => $this->matra ?? '-',
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

}
