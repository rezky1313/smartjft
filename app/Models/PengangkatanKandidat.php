<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengangkatanKandidat extends Model
{
    use HasFactory;

    protected $table = 'pengangkatan_kandidat';

    protected $fillable = [
        'pengangkatan_permohonan_id',
        'pegawai_id',
        'ujikom_hasil_id',
        'jabatan_asal',
        'jenjang_asal',
        'jabatan_tujuan_id',
        'jenjang_tujuan',
        'nilai_ujikom',
        'ranking',
        'formasi_tersedia',
        'status_kandidat',
        'catatan',
    ];

    protected $casts = [
        'nilai_ujikom' => 'decimal:2',
    ];

    // ─── Relasi ───

    public function permohonan()
    {
        return $this->belongsTo(PengangkatanPermohonan::class, 'pengangkatan_permohonan_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Sdmmodels::class, 'pegawai_id');
    }

    public function hasilUjikom()
    {
        return $this->belongsTo(UjikomHasil::class, 'ujikom_hasil_id');
    }

    public function jabatanTujuan()
    {
        return $this->belongsTo(Formasijabatan::class, 'jabatan_tujuan_id');
    }

    // ─── Accessor ───

    public function getLabelStatusKandidatAttribute(): string
    {
        return [
            'direkomendasikan' => 'Direkomendasikan',
            'antrian'          => 'Antrian',
            'ditolak_pusbin'   => 'Ditolak Pusbin',
        ][$this->status_kandidat] ?? $this->status_kandidat;
    }

    public function getBadgeStatusKandidatAttribute(): string
    {
        return match ($this->status_kandidat) {
            'direkomendasikan' => 'success',
            'antrian'          => 'warning',
            'ditolak_pusbin'   => 'danger',
            default            => 'secondary',
        };
    }
}
