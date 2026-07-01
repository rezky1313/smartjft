<?php

namespace App\Imports;

use App\Models\BankSoal;
use App\Models\BankSoalPilihan;
use App\Models\BankSoalImportLog;
use App\Models\SoalKategori;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BankSoalImport implements ToCollection, WithHeadingRow, SkipsOnError, SkipsErrors
{
    public int   $berhasil    = 0;
    public int   $gagal       = 0;
    public array $detailGagal = [];

    private string $namaFile;

    private array $validJenis      = ['umum', 'spesifik'];
    private array $validTingkat    = ['mudah', 'sedang', 'sulit'];
    private array $validTaksonomi  = ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta'];
    private array $validJawaban    = ['A', 'B', 'C', 'D'];
    private array $validStatus     = ['draft', 'aktif'];

    public function __construct(string $namaFile)
    {
        $this->namaFile = $namaFile;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $noBaris = $index + 2; // baris 1 = header
            $errors  = [];

            $jenis      = strtolower(trim($row['jenis'] ?? ''));
            $tingkat    = strtolower(trim($row['tingkat_kesulitan'] ?? ''));
            $taksonomi  = trim($row['taksonomi_bloom'] ?? '');
            $jawaban    = strtoupper(trim($row['jawaban_benar'] ?? ''));
            $status     = strtolower(trim($row['status'] ?? 'draft'));
            $pertanyaan = trim($row['pertanyaan'] ?? '');

            // Validasi field wajib
            if (empty($pertanyaan)) {
                $errors[] = 'Pertanyaan tidak boleh kosong';
            }
            if (!in_array($jenis, $this->validJenis)) {
                $errors[] = "Jenis tidak valid '{$jenis}' (harus: umum/spesifik)";
            }
            if (!in_array($jawaban, $this->validJawaban)) {
                $errors[] = "Jawaban benar tidak valid '{$jawaban}' (harus: A/B/C/D)";
            }
            if (!in_array($tingkat, $this->validTingkat)) {
                $errors[] = "Tingkat kesulitan tidak valid '{$tingkat}' (harus: mudah/sedang/sulit)";
            }
            if (!in_array($taksonomi, $this->validTaksonomi)) {
                $errors[] = "Taksonomi bloom tidak valid '{$taksonomi}'";
            }

            // Validasi pilihan A-D tidak boleh kosong
            foreach (['a', 'b', 'c', 'd'] as $kode) {
                if (empty(trim($row["pilihan_{$kode}"] ?? ''))) {
                    $errors[] = "Pilihan " . strtoupper($kode) . " tidak boleh kosong";
                }
            }

            // Validasi kategori jika spesifik
            $kategoriId = null;
            if ($jenis === 'spesifik') {
                if (empty($row['soal_kategori_id'])) {
                    $errors[] = 'soal_kategori_id wajib diisi untuk soal spesifik';
                } else {
                    $kategori = SoalKategori::find((int) $row['soal_kategori_id']);
                    if (!$kategori) {
                        $errors[] = "soal_kategori_id '{$row['soal_kategori_id']}' tidak ditemukan";
                    } else {
                        $kategoriId = $kategori->id;
                    }
                }
            }

            // Jika ada error → skip baris ini
            if (!empty($errors)) {
                $this->gagal++;
                $this->detailGagal[] = [
                    'baris'      => $noBaris,
                    'pertanyaan' => mb_substr($pertanyaan ?: '-', 0, 60),
                    'errors'     => $errors,
                ];
                continue;
            }

            // Simpan soal
            try {
                $soal = BankSoal::create([
                    'soal_kategori_id'  => $kategoriId,
                    'pertanyaan'        => $pertanyaan,
                    'pembahasan'        => trim($row['pembahasan'] ?? '') ?: null,
                    'tingkat_kesulitan' => $tingkat,
                    'taksonomi_bloom'   => $taksonomi,
                    'jenis'             => $jenis,
                    'status'            => in_array($status, $this->validStatus) ? $status : 'draft',
                    'dibuat_oleh'       => Auth::id(),
                    'disetujui_oleh'    => $status === 'aktif' ? Auth::id() : null,
                    'tanggal_disetujui' => $status === 'aktif' ? now() : null,
                ]);

                foreach (['A', 'B', 'C', 'D'] as $kode) {
                    BankSoalPilihan::create([
                        'bank_soal_id' => $soal->id,
                        'kode_pilihan' => $kode,
                        'teks_pilihan' => trim($row['pilihan_' . strtolower($kode)]),
                        'is_benar'     => $kode === $jawaban,
                    ]);
                }

                $this->berhasil++;

            } catch (\Exception $e) {
                $this->gagal++;
                $this->detailGagal[] = [
                    'baris'      => $noBaris,
                    'pertanyaan' => mb_substr($pertanyaan, 0, 60),
                    'errors'     => ['Error sistem: ' . $e->getMessage()],
                ];
            }
        }

        // Simpan log import
        BankSoalImportLog::create([
            'nama_file'     => $this->namaFile,
            'total_baris'   => $this->berhasil + $this->gagal,
            'berhasil'      => $this->berhasil,
            'gagal'         => $this->gagal,
            'detail_gagal'  => $this->detailGagal,
            'diimport_oleh' => Auth::id(),
        ]);
    }
}
