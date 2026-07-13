<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class PaketUjian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paket_ujian';

    protected $fillable = [
        'nama',
        'ujikom_jadwal_id',
        'deskripsi',
        'durasi_menit',
        'passing_grade',
        'jumlah_soal',
        'mode_pemilihan',
        'status',
        'acak_soal',
        'acak_pilihan',
        'dibuat_oleh',
        'durasi_menit_teknis',
        'jumlah_soal_teknis',
        'taksonomi_maks_teknis',
        'soal_kategori_id_teknis',
        'durasi_menit_mansoskul',
        'jumlah_soal_mansoskul',
        'taksonomi_maks_mansoskul',
        'matra_mansoskul',
    ];

    protected $casts = [
        'acak_soal'    => 'boolean',
        'acak_pilihan' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function soal()
    {
        return $this->hasMany(PaketUjianSoal::class, 'paket_ujian_id')->orderBy('urutan');
    }

    public function kategoriAcak()
    {
        return $this->hasMany(PaketUjianKategoriAcak::class, 'paket_ujian_id');
    }

    public function bankSoal()
    {
        return $this->belongsToMany(BankSoal::class, 'paket_ujian_soal', 'paket_ujian_id', 'bank_soal_id')
                    ->withPivot('urutan')
                    ->orderByPivot('urutan');
    }

    public function komposisiTaksonomi()
    {
        return $this->hasMany(PaketUjianKomposisiTaksonomi::class, 'paket_ujian_id');
    }

    public function kategoriTeknis()
    {
        return $this->belongsTo(SoalKategori::class, 'soal_kategori_id_teknis');
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'Draft',
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            default    => $this->status,
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'success',
            'draft'    => 'secondary',
            'nonaktif' => 'danger',
            default    => 'secondary',
        };
    }

    public function getLabelModeAttribute(): string
    {
        return match ($this->mode_pemilihan) {
            'acak_otomatis'   => 'Acak Otomatis',
            'manual'          => 'Manual',
            'sesi_taksonomi'  => '2 Sesi CAT',
            default           => $this->mode_pemilihan,
        };
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Generate set soal untuk peserta saat mulai ujian.
     * Dipanggil dari modul Ujikom Online.
     */
    public function generateSoalUntukPeserta($pesertaId): Collection
    {
        if ($this->mode_pemilihan === 'manual') {
            $soal = $this->bankSoal()
                ->where('bank_soal.status', 'aktif')
                ->with('pilihan')
                ->get();
        } elseif ($this->mode_pemilihan === 'sesi_taksonomi') {
            // Gabungan sesi Teknis + Mansoskul (dipakai untuk preview; alur ujian 2-sesi
            // yang sesungguhnya di Ujikom Online belum diimplementasikan — lihat generateSoalSesi()).
            $soal = $this->generateSoalSesi('teknis')
                ->merge($this->generateSoalSesi('mansoskul'))
                ->load('pilihan');
        } else {
            // Mode acak: ambil soal per konfigurasi kategori
            $soal = collect();

            foreach ($this->kategoriAcak as $config) {
                if ($config->jenis_soal === 'umum') {
                    // BankSoal::umum() sudah diarahkan ke jenis='mansoskul' (rename bank_soal.jenis)
                    $pool = BankSoal::umum()
                        ->aktif()
                        ->with('pilihan')
                        ->inRandomOrder()
                        ->take($config->jumlah_soal)
                        ->get();
                } else {
                    $pool = BankSoal::aktif()
                        ->where('soal_kategori_id', $config->soal_kategori_id)
                        ->with('pilihan')
                        ->inRandomOrder()
                        ->take($config->jumlah_soal)
                        ->get();
                }
                $soal = $soal->merge($pool);
            }
        }

        // Acak urutan soal
        if ($this->acak_soal) {
            $soal = $soal->shuffle();
        }

        // Acak pilihan jawaban per soal
        if ($this->acak_pilihan) {
            $soal = $soal->map(function ($s) {
                $s->pilihan_acak = $s->pilihan->shuffle();
                return $s;
            });
        }

        return $soal;
    }

    /**
     * Hitung total soal tersedia di bank berdasarkan konfigurasi acak.
     * Return array ['cukup' => bool, 'detail' => [...]]
     */
    public function cekKetersediaanSoal(): array
    {
        if ($this->mode_pemilihan === 'manual') {
            $jumlahDipilih = $this->soal()->count();
            return [
                'cukup'  => $jumlahDipilih >= $this->jumlah_soal,
                'detail' => ["Soal terpilih: {$jumlahDipilih} dari {$this->jumlah_soal} yang dibutuhkan"],
            ];
        }

        if ($this->mode_pemilihan === 'sesi_taksonomi') {
            return $this->cekKetersediaanKomposisi();
        }

        $detail = [];
        $semuaCukup = true;

        foreach ($this->kategoriAcak as $config) {
            if ($config->jenis_soal === 'umum') {
                $tersedia = BankSoal::umum()->aktif()->count();
                $label    = 'Soal Umum';
            } else {
                $tersedia = BankSoal::aktif()->where('soal_kategori_id', $config->soal_kategori_id)->count();
                $label    = $config->kategori?->nama ?? "Kategori #{$config->soal_kategori_id}";
            }

            $cukup = $tersedia >= $config->jumlah_soal;
            if (!$cukup) $semuaCukup = false;

            $detail[] = [
                'label'     => $label,
                'butuh'     => $config->jumlah_soal,
                'tersedia'  => $tersedia,
                'cukup'     => $cukup,
            ];
        }

        return ['cukup' => $semuaCukup, 'detail' => $detail];
    }

    /**
     * Ketersediaan soal aktif per baris komposisi taksonomi (mode sesi_taksonomi).
     */
    public function cekKetersediaanKomposisi(): array
    {
        $detail     = [];
        $semuaCukup = true;

        foreach ($this->komposisiTaksonomi as $k) {
            $query = BankSoal::aktif()->where('taksonomi_bloom', $k->taksonomi);

            if ($k->jenis_sesi === 'teknis') {
                $query->where('jenis', 'teknis')->where('soal_kategori_id', $this->soal_kategori_id_teknis);
                $labelSesi = 'Teknis';
            } else {
                $query->where('jenis', 'mansoskul')->where('matra', $this->matra_mansoskul);
                $labelSesi = 'Mansoskul';
            }

            $tersedia = $query->count();
            $cukup    = $tersedia >= $k->jumlah_soal;
            if (!$cukup) $semuaCukup = false;

            $detail[] = [
                'label'    => "{$labelSesi} — {$k->label_taksonomi}",
                'butuh'    => $k->jumlah_soal,
                'tersedia' => $tersedia,
                'cukup'    => $cukup,
            ];
        }

        return ['cukup' => $semuaCukup, 'detail' => $detail];
    }

    /**
     * Hitung komposisi jumlah soal per taksonomi secara proporsional (bobot berjenjang
     * 1..n) sampai taksonomi maksimal yang dipilih. Contoh: maks C3 -> bobot C1:C2:C3 = 1:2:3.
     */
    public function hitungKomposisiTaksonomi(string $taksonomiMaks, int $totalSoal): array
    {
        $urutan = ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta'];
        $n = array_search($taksonomiMaks, $urutan) + 1;
        $levelAktif = array_slice($urutan, 0, $n);
        $totalBobot = $n * ($n + 1) / 2;
        $komposisi = [];
        $terpakai = 0;

        foreach ($levelAktif as $idx => $taksonomi) {
            $level = $idx + 1;
            $jumlah = (int) round(($level / $totalBobot) * $totalSoal);
            $komposisi[$taksonomi] = $jumlah;
            $terpakai += $jumlah;
        }

        $selisih = $totalSoal - $terpakai;
        if ($selisih !== 0) {
            $taksonomiTertinggi = end($levelAktif);
            $komposisi[$taksonomiTertinggi] += $selisih;
        }

        return $komposisi;
    }

    /**
     * Generate ulang baris paket_ujian_komposisi_taksonomi berdasarkan konfigurasi
     * taksonomi_maks_teknis/mansoskul + jumlah_soal_teknis/mansoskul saat ini.
     */
    public function generateKomposisiTaksonomi(): void
    {
        $this->komposisiTaksonomi()->delete();

        if ($this->taksonomi_maks_teknis && $this->jumlah_soal_teknis) {
            foreach ($this->hitungKomposisiTaksonomi($this->taksonomi_maks_teknis, $this->jumlah_soal_teknis) as $tax => $jml) {
                if ($jml > 0) {
                    $this->komposisiTaksonomi()->create(['jenis_sesi' => 'teknis', 'taksonomi' => $tax, 'jumlah_soal' => $jml]);
                }
            }
        }
        if ($this->taksonomi_maks_mansoskul && $this->jumlah_soal_mansoskul) {
            foreach ($this->hitungKomposisiTaksonomi($this->taksonomi_maks_mansoskul, $this->jumlah_soal_mansoskul) as $tax => $jml) {
                if ($jml > 0) {
                    $this->komposisiTaksonomi()->create(['jenis_sesi' => 'mansoskul', 'taksonomi' => $tax, 'jumlah_soal' => $jml]);
                }
            }
        }
    }

    /**
     * Ambil set soal aktif untuk satu sesi ('teknis' atau 'mansoskul') sesuai komposisi taksonomi.
     */
    public function generateSoalSesi(string $jenisSesi): Collection
    {
        $komposisi    = $this->komposisiTaksonomi()->where('jenis_sesi', $jenisSesi)->get();
        $soalTerpilih = collect();

        foreach ($komposisi as $k) {
            $query = BankSoal::where('status', 'aktif')->where('taksonomi_bloom', $k->taksonomi);
            if ($jenisSesi === 'teknis') {
                $query->where('jenis', 'teknis')->where('soal_kategori_id', $this->soal_kategori_id_teknis);
            } else {
                $query->where('jenis', 'mansoskul')->where('matra', $this->matra_mansoskul);
            }
            $soal = $query->inRandomOrder()->take($k->jumlah_soal)->get();
            $soalTerpilih = $soalTerpilih->merge($soal);
        }

        return $soalTerpilih->shuffle();
    }
}
