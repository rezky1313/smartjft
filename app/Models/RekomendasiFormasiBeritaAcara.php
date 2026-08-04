<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiBeritaAcara extends Model
{
    protected $table = 'rekomendasi_formasi_berita_acara';

    protected $fillable = [
        'usulan_id',
        'nomor_ba',
        'tanggal_verifikasi',
        'ttd_pusbin_oleh',
        'ttd_pusbin_at',
        'ttd_pusbin_ip',
        'ttd_pengusul_oleh',
        'ttd_pengusul_at',
        'ttd_pengusul_ip',
    ];

    protected $casts = [
        'tanggal_verifikasi' => 'datetime',
        'ttd_pusbin_at' => 'datetime',
        'ttd_pengusul_at' => 'datetime',
    ];

    public function usulan()
    {
        return $this->belongsTo(RekomendasiFormasiUsulan::class, 'usulan_id');
    }

    public function ttdPusbinOleh()
    {
        return $this->belongsTo(User::class, 'ttd_pusbin_oleh');
    }

    public function ttdPengusulOleh()
    {
        return $this->belongsTo(User::class, 'ttd_pengusul_oleh');
    }

    public function getSudahLengkapAttribute(): bool
    {
        return (bool) $this->ttd_pusbin_oleh && (bool) $this->ttd_pengusul_oleh;
    }
}
