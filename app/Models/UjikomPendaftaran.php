<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UjikomPendaftaran extends Model
{
    use HasFactory;

    protected $table = 'ujikom_pendaftaran';

    protected $fillable = [
        'kode_pendaftaran',
        'ujikom_jadwal_id',
        'unit_kerja_id',
        'jenis_pendaftaran',
        'status',
        'catatan_admin_unit',
        'catatan_pusbin',
        'dibuat_oleh',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function unitKerja()
    {
        return $this->belongsTo(Rumahsakit::class, 'unit_kerja_id', 'no_rs');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function peserta()
    {
        return $this->hasMany(UjikomPendaftaranPeserta::class, 'ujikom_pendaftaran_id');
    }

    public function berkas()
    {
        return $this->hasMany(UjikomPendaftaranBerkas::class, 'ujikom_pendaftaran_id');
    }

    // ─── Accessor ─────────────────────────────────────────────────────────────

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'                   => 'Draft',
            'diajukan_admin_unit'     => 'Diajukan (Admin Unit)',
            'diverifikasi_admin_unit' => 'Diverifikasi Admin Unit',
            'diajukan_pusbin'         => 'Diajukan ke Pusbin',
            'diverifikasi_pusbin'     => 'Diverifikasi Pusbin',
            'ditolak_admin_unit'      => 'Ditolak Admin Unit',
            'ditolak_pusbin'          => 'Ditolak Pusbin',
            'selesai'                 => 'Selesai',
            default                   => '-',
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'                   => 'secondary',
            'diajukan_admin_unit'     => 'primary',
            'diverifikasi_admin_unit' => 'warning',
            'diajukan_pusbin'         => 'info',
            'diverifikasi_pusbin'     => 'success',
            'ditolak_admin_unit'      => 'danger',
            'ditolak_pusbin'          => 'danger',
            'selesai'                 => 'success',
            default                   => 'secondary',
        };
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    public static function generateKode(): string
    {
        $now    = Carbon::now();
        $bulan  = toRoman($now->month);
        $tahun  = $now->year;
        $urut   = static::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('DAFTAR-UJIKOM/%s/%d/%04d', $bulan, $tahun, $urut);
    }

    public function bisaDiedit(): bool
    {
        return $this->status === 'draft';
    }

    public function bisaDihapus(): bool
    {
        return $this->status === 'draft';
    }

    public function bisaDiajukan(): bool
    {
        return $this->status === 'draft';
    }
}
