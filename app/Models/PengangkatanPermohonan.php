<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PengangkatanPermohonan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengangkatan_permohonan';

    protected $fillable = [
        'kode_permohonan',
        'unit_kerja_id',
        'file_surat_permohonan',
        'tanggal_permohonan',
        'status',
        'catatan_pusbin',
        'diajukan_oleh',
        'diproses_oleh',
        'tanggal_disetujui',
    ];

    protected $casts = [
        'tanggal_permohonan' => 'date',
        'tanggal_disetujui'  => 'date',
    ];

    // ─── Relasi ───

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function pemroses()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function kandidat()
    {
        return $this->hasMany(PengangkatanKandidat::class, 'pengangkatan_permohonan_id');
    }

    public function surat()
    {
        return $this->hasOne(PengangkatanSurat::class, 'pengangkatan_permohonan_id');
    }

    // ─── Accessor ───

    public function getLabelStatusAttribute(): string
    {
        return [
            'draft'        => 'Draft',
            'diajukan'     => 'Diajukan',
            'menunggu_ttd' => 'Menunggu TTD',
            'ditolak'      => 'Ditolak',
            'selesai'      => 'Selesai',
        ][$this->status] ?? $this->status;
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'        => 'secondary',
            'diajukan'     => 'primary',
            'menunggu_ttd' => 'warning',
            'ditolak'      => 'danger',
            'selesai'      => 'success',
            default        => 'secondary',
        };
    }

    // ─── Helper ───

    public static function generateKode(): string
    {
        $now   = Carbon::now();
        $bulan = toRoman($now->month);
        $tahun = $now->year;
        $urut  = static::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('ANGKAT/%s/%d/%04d', $bulan, $tahun, $urut);
    }

    /**
     * Selesaikan permohonan: update formasi pegawai yang direkomendasikan.
     */
    public function selesaikan(): void
    {
        foreach ($this->kandidat()->where('status_kandidat', 'direkomendasikan')->get() as $kandidat) {
            $pegawai = $kandidat->pegawai;
            if (!$pegawai) continue;

            // Update formasi pegawai ke jabatan tujuan
            $pegawai->update([
                'formasi_jabatan_id' => $kandidat->jabatan_tujuan_id,
                'tmt_pengangkatan'   => now()->toDateString(),
            ]);
        }

        $this->update(['status' => 'selesai']);
    }
}
