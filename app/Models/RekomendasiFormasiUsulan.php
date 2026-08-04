<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekomendasiFormasiUsulan extends Model
{
    protected $table = 'rekomendasi_formasi_usulan';

    protected $fillable = [
        'kode_jf',
        'unit_kerja_id',
        'jenis_instansi',
        'tahun',
        'diajukan_oleh',
        'status',
        'catatan_override',
    ];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function variabel()
    {
        return $this->hasOne(RekomendasiFormasiVariabel::class, 'usulan_id');
    }

    public function hasil()
    {
        return $this->hasMany(RekomendasiFormasiHasil::class, 'usulan_id');
    }

    public function pegawaiExisting()
    {
        return $this->hasMany(RekomendasiFormasiPegawaiExisting::class, 'usulan_id');
    }

    public function beritaAcara()
    {
        return $this->hasOne(RekomendasiFormasiBeritaAcara::class, 'usulan_id');
    }

    public function surat()
    {
        return $this->hasOne(RekomendasiFormasiSurat::class, 'usulan_id');
    }

    public function getLabelStatusAttribute(): string
    {
        return [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'verifikasi_disepakati' => 'Verifikasi Disepakati',
            'menunggu_ttd_ba' => 'Menunggu TTD Berita Acara',
            'ba_selesai' => 'Berita Acara Selesai',
            'menunggu_ttd_rekomendasi' => 'Menunggu TTD Rekomendasi',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ][$this->status] ?? $this->status;
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'diajukan' => 'primary',
            'menunggu_verifikasi' => 'warning',
            'verifikasi_disepakati' => 'info',
            'menunggu_ttd_ba', 'menunggu_ttd_rekomendasi' => 'warning',
            'ba_selesai' => 'info',
            'selesai' => 'success',
            'ditolak' => 'danger',
            default => 'secondary',
        };
    }
}
