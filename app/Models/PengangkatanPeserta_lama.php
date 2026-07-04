<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengangkatanPeserta extends Model
{
    use SoftDeletes;

    protected $table = 'pengangkatan_peserta';

    protected $fillable = [
        'pengangkatan_permohonan_id',
        'pegawai_id',
        'jabatan_asal',
        'jenjang_asal',
        'unit_kerja_asal',
        'jabatan_tujuan_id',
        'jenjang_tujuan',
        'unit_kerja_tujuan_id',
        'ujikom_peserta_id',
        'status_validasi_formasi',
        'status_validasi_ujikom',
        'catatan',
    ];

    /**
     * Relasi ke Permohonan
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PengangkatanPermohonan::class, 'pengangkatan_permohonan_id');
    }

    /**
     * Relasi ke Pegawai (SDM)
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Sdmmodels::class, 'pegawai_id');
    }

    /**
     * Relasi ke Jabatan Tujuan (Formasi)
     */
    public function jabatanTujuan(): BelongsTo
    {
        return $this->belongsTo(Formasijabatan::class, 'jabatan_tujuan_id');
    }

    /**
     * Relasi ke Unit Kerja Tujuan
     */
    public function unitKerjaTujuan(): BelongsTo
    {
        return $this->belongsTo(Rumahsakit::class, 'unit_kerja_tujuan_id', 'no_rs');
    }

    /**
     * Relasi ke Ujikom Peserta (jika ada)
     */
    public function ujikomPeserta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UjikomPeserta::class, 'ujikom_peserta_id');
    }

    /**
     * Get status validasi formasi badge color
     */
    public function getFormasiBadgeColorAttribute(): string
    {
        return match($this->status_validasi_formasi) {
            'tersedia' => 'success',
            'tidak_tersedia' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status validasi formasi label
     */
    public function getFormasiLabelAttribute(): string
    {
        return match($this->status_validasi_formasi) {
            'tersedia' => 'Tersedia',
            'tidak_tersedia' => 'Tidak Tersedia',
            default => 'Unknown',
        };
    }

    /**
     * Get status validasi ujikom badge color
     */
    public function getUjikomBadgeColorAttribute(): string
    {
        return match($this->status_validasi_ujikom) {
            'memenuhi' => 'success',
            'tidak_memenuhi' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get status validasi ujikom label
     */
    public function getUjikomLabelAttribute(): string
    {
        return match($this->status_validasi_ujikom) {
            'memenuhi' => 'Memenuhi',
            'tidak_memenuhi' => 'Tidak Memenuhi',
            default => 'Unknown',
        };
    }

    /**
     * Cek formasi tersedia atau tidak
     * Menghitung sisa formasi: kuota - terisi
     */
    public static function cekFormasi($jabatanId, $unitKerjaId, $tahun): array
    {
        $formasi = Formasijabatan::where('id', $jabatanId)
            ->where('unit_kerja_id', $unitKerjaId)
            ->where('tahun_formasi', $tahun)
            ->first();

        if (!$formasi) {
            return [
                'tersedia' => false,
                'sisa' => 0,
                'kuota' => 0,
                'terisi' => 0,
                'pesan' => 'Formasi tidak ditemukan untuk jabatan, unit kerja, dan tahun yang dipilih.'
            ];
        }

        $kuota = $formasi->kuota ?? 0;
        $terisi = $formasi->terisi ?? 0;
        $sisa = $kuota - $terisi;

        return [
            'tersedia' => $sisa > 0,
            'sisa' => $sisa,
            'kuota' => $kuota,
            'terisi' => $terisi,
            'pesan' => $sisa > 0
                ? "Formasi tersedia (sisa {$sisa} dari {$kuota})"
                : "Formasi tidak tersedia pada jabatan ini di unit kerja yang dipilih (kuota: {$kuota}, terisi: {$terisi})"
        ];
    }

    /**
     * Cek hasil uji kompetensi pegawai
     * Mencari record ujikom_peserta terbaru milik pegawai tersebut
     */
    public static function cekUjikom($pegawaiId): array
    {
        $ujikomPeserta = \App\Models\UjikomPeserta::with('permohonan')
            ->where('pegawai_id', $pegawaiId)
            ->whereIn('hasil', ['lulus', 'tidak_lulus'])
            ->latest()
            ->first();

        if (!$ujikomPeserta) {
            return [
                'memenuhi' => false,
                'hasil' => null,
                'ujikom_peserta_id' => null,
                'pesan' => 'Pegawai belum mengikuti uji kompetensi, disarankan ujikom terlebih dahulu'
            ];
        }

        $hasil = $ujikomPeserta->hasil;
        $memenuhi = in_array($hasil, ['lulus', 'tidak_lulus']);

        return [
            'memenuhi' => $memenuhi,
            'hasil' => $hasil,
            'ujikom_peserta_id' => $ujikomPeserta->id,
            'pesan' => "Telah mengikuti ujikom (hasil: {$hasil})"
        ];
    }
}
