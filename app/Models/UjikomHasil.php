<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomHasil extends Model
{
    use HasFactory;

    protected $table = 'ujikom_hasil';

    protected $fillable = [
        'ujikom_jadwal_id',
        'ujikom_sesi_id',
        'peserta_id',
        'jenis_ujian',
        'nilai',
        'nilai_teknis',
        'nilai_mansoskul',
        'nilai_teknis_cat',
        'nilai_teknis_wawancara',
        'nilai_teknis_presentasi',
        'nilai_mansoskul_cat',
        'nilai_mansoskul_wawancara',
        'nilai_mansoskul_presentasi',
        'bobot_teknis',
        'bobot_mansoskul',
        'status_kelulusan',
        'status_kecurangan',
        'passing_grade',
        'catatan_admin',
        'dinilai_oleh',
        'tanggal_ujian',
    ];

    protected $casts = [
        'nilai'                      => 'decimal:2',
        'nilai_teknis'               => 'decimal:2',
        'nilai_mansoskul'            => 'decimal:2',
        'nilai_teknis_cat'           => 'decimal:2',
        'nilai_teknis_wawancara'     => 'decimal:2',
        'nilai_teknis_presentasi'    => 'decimal:2',
        'nilai_mansoskul_cat'        => 'decimal:2',
        'nilai_mansoskul_wawancara'  => 'decimal:2',
        'nilai_mansoskul_presentasi' => 'decimal:2',
        'tanggal_ujian'              => 'date',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function sesi()
    {
        return $this->belongsTo(UjikomSesi::class, 'ujikom_sesi_id');
    }

    public function peserta()
    {
        return $this->belongsTo(UjikomPendaftaranPeserta::class, 'peserta_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status_kelulusan) {
            'lulus'         => 'Lulus',
            'tidak_lulus'   => 'Tidak Lulus',
            'belum_dinilai' => 'Belum Dinilai',
            default         => $this->status_kelulusan,
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status_kelulusan) {
            'lulus'         => 'success',
            'tidak_lulus'   => 'danger',
            'belum_dinilai' => 'secondary',
            default         => 'secondary',
        };
    }

    public function getLabelJenisAttribute(): string
    {
        return match ($this->jenis_ujian) {
            'online'  => 'Online',
            'offline' => 'Offline',
            default   => $this->jenis_ujian,
        };
    }

    public function getLabelKecuranganAttribute(): string
    {
        return match ($this->status_kecurangan) {
            'terindikasi' => 'Terindikasi Kecurangan',
            default       => 'Normal',
        };
    }

    public function getBadgeKecuranganAttribute(): string
    {
        return match ($this->status_kecurangan) {
            'terindikasi' => 'dark',
            default       => 'success',
        };
    }
}
