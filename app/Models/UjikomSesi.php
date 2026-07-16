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
        'sesi_teknis_id',
        'jenis_sesi',
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

    /**
     * Sesi Teknis dari pasangan sesi ini (kalau sesi ini adalah sesi Mansoskul).
     */
    public function sesiTeknis()
    {
        return $this->belongsTo(UjikomSesi::class, 'sesi_teknis_id');
    }

    /**
     * Sesi Mansoskul dari pasangan sesi ini (kalau sesi ini adalah sesi Teknis).
     */
    public function sesiMansoskul()
    {
        return $this->hasOne(UjikomSesi::class, 'sesi_teknis_id');
    }

    public function pelanggaran()
    {
        return $this->hasMany(UjikomPelanggaran::class, 'ujikom_sesi_id');
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getSisaWaktuAttribute(): int
    {
        if (!$this->batas_waktu || in_array($this->status_sesi, ['selesai', 'timeout', 'disubmit_paksa'])) {
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
            'menunggu'       => 'Menunggu',
            'berlangsung'    => 'Berlangsung',
            'selesai'        => 'Selesai',
            'timeout'        => 'Timeout',
            'disubmit_paksa' => 'Disubmit Paksa (Pelanggaran)',
            default          => $this->status_sesi,
        };
    }

    public function getBadgeStatusSesiAttribute(): string
    {
        return match ($this->status_sesi) {
            'menunggu'       => 'secondary',
            'berlangsung'    => 'primary',
            'selesai'        => 'success',
            'timeout'        => 'danger',
            'disubmit_paksa' => 'dark',
            default          => 'secondary',
        };
    }

    public function getLabelJenisSesiAttribute(): string
    {
        return match ($this->jenis_sesi) {
            'teknis'    => 'Sesi 1 — Teknis',
            'mansoskul' => 'Sesi 2 — Mansoskul',
            'tunggal'   => 'Sesi Tunggal',
            default     => $this->jenis_sesi,
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
                'ujikom_sesi_id'    => $fresh->id,
                'jenis_ujian'       => 'online',
                'nilai'             => $fresh->nilai_akhir,
                'status_kelulusan'  => $fresh->status_lulus,
                'passing_grade'     => $fresh->paketUjian->passing_grade ?? null,
                'tanggal_ujian'     => $fresh->waktu_selesai?->toDateString(),
                'dinilai_oleh'      => null,
                'status_kecurangan' => $fresh->pelanggaran()->count() >= 3 ? 'terindikasi' : 'normal',
            ]
        );
    }

    /**
     * Hitung nilai sesi CAT ini saja (0-100), TANPA menyimpan/sync ke ujikom_hasil —
     * dipakai khusus alur 2 sesi (jenis_sesi teknis/mansoskul), finalisasi digabung
     * lewat UjikomOnlineController::cobaFinalisasiHasil() setelah kedua sesi selesai.
     * Teknis: persentase jawaban benar. Mansoskul: rata-rata nilai_diperoleh (skala 1-5) -> 0-100.
     */
    public function hitungNilaiSesi(): float
    {
        $totalSoal = $this->soal()->count();
        if ($totalSoal === 0) {
            return 0;
        }

        if ($this->jenis_sesi === 'mansoskul') {
            $totalNilai    = (int) $this->soal()->sum('nilai_diperoleh');
            $maksimalNilai = $totalSoal * 5;
            return $maksimalNilai > 0 ? round(($totalNilai / $maksimalNilai) * 100, 2) : 0;
        }

        $benar = $this->soal()->where('is_benar', true)->count();
        return round(($benar / $totalSoal) * 100, 2);
    }

    /**
     * Hitung nilai gabungan 1 kompetensi (Teknis atau Mansoskul), menggabungkan
     * CAT + Wawancara + Presentasi sesuai bobot aspek aktif di Jadwal.
     *
     * @param  string     $kompetensi       'teknis' | 'mansoskul'
     * @param  float      $nilaiCat         nilai sesi CAT (0-100)
     * @param  float|null $nilaiWawancara   skala 1-5, null jika tidak aktif/belum dinilai
     * @param  float|null $nilaiPresentasi  skala 1-5, null jika tidak aktif/belum dinilai
     * @param  array      $bobotAspek       hasil UjikomJadwal::getBobotAspek($kompetensi)
     */
    public static function hitungNilaiKompetensi(
        string $kompetensi,
        float $nilaiCat,
        ?float $nilaiWawancara,
        ?float $nilaiPresentasi,
        array $bobotAspek
    ): float {
        $total = $nilaiCat * ($bobotAspek['cat'] / 100);

        if ($bobotAspek['wawancara'] > 0 && $nilaiWawancara !== null) {
            $wawancaraSkor100 = ($nilaiWawancara / 5) * 100;
            $total += $wawancaraSkor100 * ($bobotAspek['wawancara'] / 100);
        }

        if ($bobotAspek['presentasi'] > 0 && $nilaiPresentasi !== null) {
            $presentasiSkor100 = ($nilaiPresentasi / 5) * 100;
            $total += $presentasiSkor100 * ($bobotAspek['presentasi'] / 100);
        }

        return round($total, 2);
    }
}
