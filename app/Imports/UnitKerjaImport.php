<?php

namespace App\Imports;

use App\Models\Province;
use App\Models\Regency;
use App\Models\UnitKerja;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UnitKerjaImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    public int $berhasil = 0;
    public int $gagal = 0;
    public array $detailGagal = [];

    protected array $allowedMatra    = ['Darat', 'Laut', 'Udara', 'Kereta'];
    protected array $allowedInstansi = ['Pusat', 'Daerah'];

    public function sheets(): array
    {
        return ['Data Unit Kerja' => $this];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $noBaris = $index + 2; // +1 heading row, +1 karena index koleksi mulai dari 0
            $errors  = [];

            $namaUnit    = trim((string) ($row['nama_unit_kerja'] ?? ''));
            $provinsi    = trim((string) ($row['provinsi'] ?? ''));
            $kabKota     = trim((string) ($row['kab_kota'] ?? ''));
            $matraRaw    = trim((string) ($row['matra'] ?? ''));
            $instansiRaw = trim((string) ($row['instansi'] ?? ''));

            if ($namaUnit === '') {
                $errors[] = 'Nama unit kerja tidak boleh kosong';
            }

            if ($kabKota === '') {
                $errors[] = 'Kabupaten/Kota tidak boleh kosong';
            }

            $matra = $this->normalizeMatra($matraRaw);
            if ($matraRaw === '') {
                $errors[] = 'Matra tidak boleh kosong';
            } elseif (!$matra) {
                $errors[] = "Nilai matra '{$matraRaw}' tidak dikenal (harus Darat/Laut/Udara/Kereta)";
            }

            $instansi = $this->normalizeInstansi($instansiRaw);
            if ($instansiRaw === '') {
                $errors[] = 'Instansi tidak boleh kosong';
            } elseif (!$instansi) {
                $errors[] = "Nilai instansi '{$instansiRaw}' tidak dikenal (harus Pusat/Daerah)";
            }

            $regencyId = null;
            if ($kabKota !== '') {
                $regencyId = $this->resolveRegencyId($provinsi, $kabKota);
                if (!$regencyId) {
                    $errors[] = "Provinsi/Kab-Kota '{$provinsi} / {$kabKota}' tidak ditemukan di master data wilayah";
                }
            }

            if (!empty($errors)) {
                $this->gagal++;
                $this->detailGagal[] = ['baris' => $noBaris, 'nama' => $namaUnit ?: '-', 'errors' => $errors];
                continue;
            }

            try {
                UnitKerja::create([
                    'nama_unit_kerja' => $namaUnit,
                    'alamat'          => trim((string) ($row['alamat'] ?? '')) ?: null,
                    'no_telp'         => trim((string) ($row['no_telp'] ?? '')) ?: null,
                    'regency_id'      => $regencyId,
                    'matra'           => $matra,
                    'instansi'        => $instansi,
                    'latitude'        => is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
                    'longitude'       => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
                ]);
                $this->berhasil++;
            } catch (\Exception $e) {
                $this->gagal++;
                $this->detailGagal[] = ['baris' => $noBaris, 'nama' => $namaUnit ?: '-', 'errors' => ['Error sistem: ' . $e->getMessage()]];
            }
        }
    }

    private function normalizeMatra(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }
        $m = Str::upper($raw);
        if (Str::contains($m, 'DARAT')) {
            $m = 'DARAT';
        } elseif (Str::contains($m, 'LAUT')) {
            $m = 'LAUT';
        } elseif (Str::contains($m, 'UDARA')) {
            $m = 'UDARA';
        } elseif (Str::contains($m, 'KERETA')) {
            $m = 'KERETA';
        }
        $title = Str::title(Str::lower($m));
        return in_array($title, $this->allowedMatra, true) ? $title : null;
    }

    private function normalizeInstansi(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }
        $i = Str::upper($raw);
        if (Str::contains($i, 'PUSAT')) {
            $i = 'PUSAT';
        } elseif (Str::contains($i, 'DAERAH')) {
            $i = 'DAERAH';
        }
        $title = Str::title(Str::lower($i));
        return in_array($title, $this->allowedInstansi, true) ? $title : null;
    }

    /**
     * Resolve regency dari nama Provinsi + Kab/Kota — logic sama dengan
     * GenerateUnitKerjaSeederFromExcel (command import lama), supaya konsisten.
     */
    private function resolveRegencyId(string $provinsi, string $kabKota): ?int
    {
        $typeHint = null;
        $name  = trim($kabKota);
        $lower = Str::lower($name);
        if (Str::startsWith($lower, 'kota ')) {
            $typeHint = 'KOTA';
            $name = trim(Str::after($name, ' '));
        } elseif (Str::startsWith($lower, 'kabupaten ')) {
            $typeHint = 'KABUPATEN';
            $name = trim(Str::after($name, ' '));
        }

        $query = Regency::query()->where('name', $name);
        if ($provinsi !== '') {
            $provinceId = Province::whereRaw('LOWER(name) = ?', [mb_strtolower($provinsi)])->value('id');
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
        }

        $list = $query->get(['id', 'type']);
        if ($list->count() === 1) {
            return $list->first()->id;
        }
        if ($list->count() > 1) {
            $found = $typeHint ? $list->firstWhere('type', $typeHint) : null;
            return ($found ?: $list->firstWhere('type', 'KOTA') ?: $list->first())->id;
        }

        return null;
    }
}
