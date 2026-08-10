<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UjikomNilaiManual extends Model
{
    protected $table = 'ujikom_nilai_manual';

    protected $fillable = [
        'ujikom_jadwal_id',
        'peserta_id',
        'kompetensi',
        'aspek',
        'nilai',
        'catatan',
        'dinilai_oleh',
        'dinilai_sebagai',
    ];

    protected $casts = [
        'nilai' => 'integer',
    ];

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function peserta()
    {
        return $this->belongsTo(UjikomPendaftaranPeserta::class, 'peserta_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    public function getLabelKompetensiAttribute(): string
    {
        return match ($this->kompetensi) {
            'teknis'    => 'Teknis',
            'mansoskul' => 'Mansoskul',
            default     => $this->kompetensi,
        };
    }

    public function getLabelAspekAttribute(): string
    {
        return match ($this->aspek) {
            'wawancara'  => 'Wawancara',
            'presentasi' => 'Presentasi',
            default      => $this->aspek,
        };
    }

    public function getLabelDinilaiSebagaiAttribute(): ?string
    {
        return match ($this->dinilai_sebagai) {
            'pewawancara' => 'Pewawancara',
            'penguji'     => 'Penguji',
            default       => null,
        };
    }
}
