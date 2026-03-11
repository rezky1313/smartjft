<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjikomPermohonan extends Model
{
    use SoftDeletes;

    protected $table = 'ujikom_permohonan';

    protected $fillable = [
        'nomor_permohonan',
        'unit_kerja_id',
        'file_surat_permohonan',
        'tanggal_permohonan',
        'status',
        'catatan_verifikator',
        'tanggal_jadwal',
        'tempat_ujikom',
        'created_by',
    ];

    protected $casts = [
        'tanggal_permohonan' => 'date',
        'tanggal_jadwal' => 'date',
    ];

    /**
     * Relasi ke Unit Kerja (Rumahsakit)
     */
    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(Rumahsakit::class, 'unit_kerja_id', 'no_rs');
    }

    /**
     * Relasi ke User yang membuat permohonan
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Peserta (many-to-many dengan SDM)
     */
    public function peserta(): HasMany
    {
        return $this->hasMany(UjikomPeserta::class, 'ujikom_permohonan_id');
    }

    /**
     * Relasi ke Berita Acara
     */
    public function beritaAcara(): HasMany
    {
        return $this->hasMany(UjikomBeritaAcara::class, 'ujikom_permohonan_id');
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeWithStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan unit kerja
     */
    public function scopeWithUnitKerja($query, $unitKerjaId)
    {
        if ($unitKerjaId) {
            return $query->where('unit_kerja_id', $unitKerjaId);
        }
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeWithTahun($query, $tahun)
    {
        if ($tahun) {
            return $query->whereYear('tanggal_permohonan', $tahun);
        }
        return $query;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'diajukan' => 'primary',
            'diverifikasi' => 'warning',
            'terjadwal' => 'info',
            'selesai_uji' => 'orange',
            'hasil_diinput' => 'teal',
            'selesai' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'diverifikasi' => 'Diverifikasi',
            'terjadwal' => 'Terjadwal',
            'selesai_uji' => 'Selesai Uji',
            'hasil_diinput' => 'Hasil Diinput',
            'selesai' => 'Selesai',
            default => 'Unknown',
        };
    }

    /**
     * Generate nomor permohonan otomatis
     */
    public static function generateNomorPermohonan($tanggal = null): string
    {
        if ($tanggal === null) {
            $tanggal = now();
        } else {
            $tanggal = \Carbon\Carbon::parse($tanggal);
        }

        $month = $tanggal->format('m');
        $year = $tanggal->format('Y');

        // Hitung jumlah permohonan di bulan dan tahun yang sama
        $count = self::whereYear('tanggal_permohonan', $year)
            ->whereMonth('tanggal_permohonan', $month)
            ->count();

        $noUrut = $count + 1;

        return formatNomorPermohonanUjikom($noUrut, $tanggal->format('Y-m-d'));
    }

    /**
     * Cek apakah bisa diedit (hanya status draft)
     */
    public function bisaDiedit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah bisa dihapus (hanya status draft)
     */
    public function bisaDihapus(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah bisa diajukan (hanya status draft)
     */
    public function bisaDiajukan(): bool
    {
        return $this->status === 'draft' && $this->peserta()->count() > 0;
    }
}
