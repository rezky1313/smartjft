<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengangkatanPermohonan extends Model
{
    use SoftDeletes;

    protected $table = 'pengangkatan_permohonan';

    protected $fillable = [
        'nomor_permohonan',
        'jalur',
        'unit_kerja_id',
        'file_surat_permohonan',
        'tanggal_permohonan',
        'status',
        'catatan_verifikator',
        'created_by',
    ];

    protected $casts = [
        'tanggal_permohonan' => 'date',
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
     * Relasi ke Peserta
     */
    public function peserta(): HasMany
    {
        return $this->hasMany(PengangkatanPeserta::class, 'pengangkatan_permohonan_id');
    }

    /**
     * Relasi ke Surat Pertimbangan
     */
    public function surat(): HasMany
    {
        return $this->hasMany(PengangkatanSurat::class, 'pengangkatan_permohonan_id');
    }

    /**
     * Get surat pertimbangan terbaru
     */
    public function suratPertimbangan(): BelongsTo
    {
        return $this->belongsTo(PengangkatanSurat::class, 'id', 'pengangkatan_permohonan_id')
            ->latest('tanggal_dibuat');
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
            'draft_surat' => 'orange',
            'paraf_katim' => 'teal',
            'paraf_kabid' => 'indigo',
            'tanda_tangan' => 'pink',
            'penomoran' => 'lime',
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
            'draft_surat' => 'Draft Surat',
            'paraf_katim' => 'Paraf Katim',
            'paraf_kabid' => 'Paraf Kabid',
            'tanda_tangan' => 'Tanda Tangan',
            'penomoran' => 'Penomoran',
            'selesai' => 'Selesai',
            default => 'Unknown',
        };
    }

    /**
     * Get label jalur
     */
    public function getLabelJalurAttribute(): string
    {
        return [
            'pengangkatan_pertama'  => 'Pengangkatan Pertama',
            'inpasing'              => 'Penyesuaian/Inpasing',
            'kenaikan_jenjang'      => 'Kenaikan Jenjang',
            'promosi'               => 'Promosi',
            'perpindahan_kategori'  => 'Perpindahan Kategori',
            'perpindahan_jabatan'   => 'Perpindahan dari Jabatan Lain',
            'pengangkatan_kembali'  => 'Pengangkatan Kembali',
        ][$this->jalur] ?? $this->jalur;
    }

    /**
     * Get badge jalur
     */
    public function getBadgeJalurAttribute(): string
    {
        return [
            'pengangkatan_pertama'  => 'bg-primary',
            'inpasing'              => 'bg-info',
            'kenaikan_jenjang'      => 'bg-success',
            'promosi'               => 'bg-warning text-dark',
            'perpindahan_kategori'  => 'bg-secondary',
            'perpindahan_jabatan'   => 'bg-purple',
            'pengangkatan_kembali'  => 'bg-dark',
        ][$this->jalur] ?? 'bg-secondary';
    }

    /**
     * Get jalur label (legacy - untuk backward compatibility)
     */
    public function getJalurLabelAttribute(): string
    {
        return $this->label_jalur;
    }

    /**
     * Get jalur badge color (legacy - untuk backward compatibility)
     */
    public function getJalurBadgeColorAttribute(): string
    {
        // Convert from badge classes (bg-primary) to old format (primary)
        $badge = $this->badge_jalur;
        return str_replace(['bg-', ' text-dark'], '', $badge);
    }

    /**
     * Generate nomor permohonan otomatis
     * Format: PANGKAT/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT]
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

        // Convert bulan ke romawi
        $romawi = self::numberToRoman((int)$month);

        // Cari nomor permohonan terakhir di bulan dan tahun yang sama (termasuk soft deleted)
        $lastNomor = self::withTrashed()
            ->whereYear('tanggal_permohonan', $year)
            ->whereMonth('tanggal_permohonan', $month)
            ->orderBy('id', 'desc')
            ->value('nomor_permohonan');

        if ($lastNomor) {
            // Extract nomor urut dari nomor permohonan terakhir
            // Format: PANGKAT/III/2026/0001
            $parts = explode('/', $lastNomor);
            $lastNoUrut = isset($parts[3]) ? (int)$parts[3] : 0;
            $noUrut = $lastNoUrut + 1;
        } else {
            // Tidak ada nomor permohonan di bulan ini, mulai dari 1
            $noUrut = 1;
        }

        $noUrutFormatted = str_pad($noUrut, 4, '0', STR_PAD_LEFT);

        return "PANGKAT/{$romawi}/{$year}/{$noUrutFormatted}";
    }

    /**
     * Helper: Convert angka ke romawi
     */
    private static function numberToRoman($num): string
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($num >= $value) {
                $result .= $roman;
                $num -= $value;
            }
        }

        return $result;
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
     * Cek apakah bisa diajukan (hanya status draft dengan minimal 1 peserta)
     */
    public function bisaDiajukan(): bool
    {
        return $this->status === 'draft' && $this->peserta()->count() > 0;
    }

    /**
     * Cek apakah bisa diverifikasi (hanya status diajukan)
     */
    public function bisaDiverifikasi(): bool
    {
        return $this->status === 'diajukan';
    }

    /**
     * Cek apakah bisa dibuatkan draft surat (hanya status diverifikasi)
     */
    public function bisaBuatDraftSurat(): bool
    {
        return $this->status === 'diverifikasi';
    }

    /**
     * Cek apakah bisa dialurkan ke tahap paraf/ttd/penomoran
     */
    public function bisaDialurkan(): bool
    {
        return in_array($this->status, [
            'draft_surat', 'paraf_katim', 'paraf_kabid', 'tanda_tangan'
        ]);
    }

    /**
     * Cek apakah bisa dinomorikan (hanya status tanda_tangan)
     */
    public function bisaDinomorikan(): bool
    {
        return $this->status === 'tanda_tangan';
    }

    /**
     * Cek apakah bisa diselesaikan (hanya status penomoran)
     */
    public function bisaDiselesaikan(): bool
    {
        return $this->status === 'penomoran';
    }
}
