<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomJadwal extends Model
{
    use HasFactory;

    protected $table = 'ujikom_jadwal';

    protected $fillable = [
        'judul',
        'jenis_ujian',
        'matra',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'tempat',
        'kuota',
        'status',
        'dibuat_oleh',
        'teknis_wawancara_aktif',
        'teknis_presentasi_aktif',
        'mansoskul_wawancara_aktif',
        'mansoskul_presentasi_aktif',
        'jenjang_tujuan',
    ];

    protected $casts = [
        'tanggal_mulai'              => 'date',
        'tanggal_selesai'            => 'date',
        'teknis_wawancara_aktif'     => 'boolean',
        'teknis_presentasi_aktif'    => 'boolean',
        'mansoskul_wawancara_aktif'  => 'boolean',
        'mansoskul_presentasi_aktif' => 'boolean',
    ];

    public function persyaratan()
    {
        return $this->hasMany(UjikomPersyaratan::class, 'ujikom_jadwal_id')->orderBy('urutan');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function hasilUjikom()
    {
        return $this->hasMany(UjikomHasil::class, 'ujikom_jadwal_id');
    }

    public function pendaftaran()
    {
        return $this->hasMany(UjikomPendaftaran::class, 'ujikom_jadwal_id');
    }

    public function getJenisUjianLabelAttribute(): string
    {
        return match ($this->jenis_ujian) {
            'kenaikan_jabatan'      => 'Kenaikan Jabatan',
            'perpindahan_jabatan'   => 'Perpindahan Jabatan',
            'penyesuaian_inpassing' => 'Penyesuaian (Inpassing)',
            default                 => '-',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'published' => 'Dipublikasikan',
            'selesai'   => 'Selesai',
            default     => '-',
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'published' => 'success',
            'selesai'   => 'dark',
            default     => 'secondary',
        };
    }

    public function getLabelJenjangTujuanAttribute(): string
    {
        return match ($this->jenjang_tujuan) {
            'ahli_utama'   => 'Ahli Utama',
            'ahli_madya'   => 'Ahli Madya',
            'ahli_muda'    => 'Ahli Muda',
            'ahli_pertama' => 'Ahli Pertama',
            'penyelia'     => 'Penyelia',
            'mahir'        => 'Mahir',
            'terampil'     => 'Terampil',
            'pemula'       => 'Pemula',
            default        => $this->jenjang_tujuan ?? '-',
        };
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Hitung bobot aspek (CAT/Wawancara/Presentasi) untuk satu kompetensi.
     * $kompetensi = 'teknis' atau 'mansoskul'. Tes CAT selalu aktif otomatis.
     */
    public function getBobotAspek(string $kompetensi): array
    {
        $wawancara  = (bool) $this->{$kompetensi . '_wawancara_aktif'};
        $presentasi = (bool) $this->{$kompetensi . '_presentasi_aktif'};

        if (!$wawancara && !$presentasi) {
            return ['cat' => 100, 'wawancara' => 0, 'presentasi' => 0];
        }
        if ($wawancara && $presentasi) {
            return ['cat' => 50, 'wawancara' => 25, 'presentasi' => 25];
        }
        if ($wawancara && !$presentasi) {
            return ['cat' => 50, 'wawancara' => 50, 'presentasi' => 0];
        }

        return ['cat' => 50, 'wawancara' => 0, 'presentasi' => 50];
    }

    /**
     * Bobot Teknis vs Mansoskul berdasarkan jenjang_tujuan, dari config/bobot_penilaian_jft.php.
     */
    public function getBobotJenjang(): array
    {
        return config('bobot_penilaian_jft.' . $this->jenjang_tujuan, ['teknis' => 70, 'mansoskul' => 30]);
    }
}
