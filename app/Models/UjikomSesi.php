<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UjikomSesi extends Model
{
    use HasFactory;

    protected $table = 'ujikom_sesi';

    protected $fillable = [
        'ujikom_jadwal_id',
        'paket_ujian_id',
        'peserta_id',
        'status_sesi',
        'waktu_mulai',
        'waktu_selesai',
        'batas_waktu',
        'nilai_akhir',
        'jumlah_benar',
        'jumlah_salah',
        'jumlah_kosong',
        'status_lulus',
        'ip_address',
    ];

    protected $casts = [
        'waktu_mulai'   => 'datetime',
        'waktu_selesai' => 'datetime',
        'batas_waktu'   => 'datetime',
        'nilai_akhir'   => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function paketUjian()
    {
        return $this->belongsTo(PaketUjian::class, 'paket_ujian_id');
    }

    public function peserta()
    {
        return $this->belongsTo(UjikomPendaftaranPeserta::class, 'peserta_id');
    }

    public function soal()
    {
        return $this->hasMany(UjikomSesiSoal::class, 'ujikom_sesi_id')->orderBy('urutan');
    }

    public function log()
    {
        return $this->hasMany(UjikomSesiLog::class, 'ujikom_sesi_id')->orderByDesc('created_at');
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getSisaWaktuAttribute(): int
    {
        if (!$this->batas_waktu || $this->status_sesi === 'selesai' || $this->status_sesi === 'timeout') {
            return 0;
        }

        $sisa = Carbon::now()->diffInSeconds($this->batas_waktu, false);
        return max(0, (int) $sisa);
    }

    public function getProgressAttribute(): array
    {
        $total   = $this->soal()->count();
        $dijawab = $this->soal()->whereNotNull('pilihan_dipilih')->count();

        return [
            'dijawab' => $dijawab,
            'total'   => $total,
            'persen'  => $total > 0 ? round(($dijawab / $total) * 100) : 0,
        ];
    }

    public function getLabelStatusSesiAttribute(): string
    {
        return match ($this->status_sesi) {
            'menunggu'    => 'Menunggu',
            'berlangsung' => 'Berlangsung',
            'selesai'     => 'Selesai',
            'timeout'     => 'Timeout',
            default       => $this->status_sesi,
        };
    }

    public function getBadgeStatusSesiAttribute(): string
    {
        return match ($this->status_sesi) {
            'menunggu'    => 'secondary',
            'berlangsung' => 'primary',
            'selesai'     => 'success',
            'timeout'     => 'danger',
            default       => 'secondary',
        };
    }

    // ─── Business Logic ──────────────────────────────────────────────────────

    public function hitungNilai(): void
    {
        $totalSoal   = $this->soal()->count();
        $benar       = $this->soal()->where('is_benar', true)->count();
        $salah       = $this->soal()->where('is_benar', false)->count();
        $kosong      = $this->soal()->whereNull('pilihan_dipilih')->count();

        $nilai = $totalSoal > 0 ? round(($benar / $totalSoal) * 100, 2) : 0;

        $passingGrade = $this->paketUjian->passing_grade ?? 0;

        $this->update([
            'jumlah_benar'  => $benar,
            'jumlah_salah'  => $salah,
            'jumlah_kosong' => $kosong,
            'nilai_akhir'   => $nilai,
            'status_lulus'  => $nilai >= $passingGrade ? 'lulus' : 'tidak_lulus',
            'status_sesi'   => 'selesai',
            'waktu_selesai' => Carbon::now(),
        ]);

        // Sync otomatis ke tabel ujikom_hasil
        $this->syncKeHasil();
    }

    /**
     * Sync hasil ujian online ke tabel ujikom_hasil (sumber kebenaran tunggal).
     */
    public function syncKeHasil(): void
    {
        $fresh = $this->fresh();

        UjikomHasil::updateOrCreate(
            [
                'ujikom_jadwal_id' => $fresh->ujikom_jadwal_id,
                'peserta_id'       => $fresh->peserta_id,
            ],
            [
                'ujikom_sesi_id'   => $fresh->id,
                'jenis_ujian'      => 'online',
                'nilai'            => $fresh->nilai_akhir,
                'status_kelulusan' => $fresh->status_lulus,
                'passing_grade'    => $fresh->paketUjian->passing_grade ?? null,
                'tanggal_ujian'    => $fresh->waktu_selesai?->toDateString(),
                'dinilai_oleh'     => null,
            ]
        );
    }
}
