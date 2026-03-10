# Dokumentasi Perbaikan Fitur Data Formasi
## Over Kuota Diizinkan, Tapi Ditandai

**Tanggal:** 10 Maret 2026
**Versi:** 1.2.0

---

## Ringkasan Perubahan

Sistem formasi diperbarui dengan pendekatan "Over Kuota Diizinkan". Pegawai tetap bisa ditambahkan meskipun kuota formasi sudah penuh, namun akan ditandai sebagai "Di Luar Formasi". Sistem ini lebih fleksibel dan memberikan transparansi penuh mengenai kelebihan kuota.

---

## 1. File Migration (Baru)

### File: `database/migrations/2025_03_10_add_status_formasi_to_sdm_table.php`

**Deskripsi:** Menambahkan kolom `status_formasi` ke tabel `sumber_daya_manusia`

**Struktur Kolom:**
- Nama: `status_formasi`
- Tipe: `ENUM`
- Values: `'terpenuhi'`, `'di_luar_formasi'`
- Default: `'terpenuhi'`
- Nullable: YES

**Perintah untuk rollback:**
```bash
php artisan migrate:rollback
```

---

## 2. File Models (Dimodifikasi)

### 2.1 File: `app/Models/Sdmmodels.php`

**Perubahan:**
```php
// Tambahkan ke $fillable array:
protected $fillable = [
    // ... existing fields ...
    'status_formasi',   // status formasi: terpenuhi / di_luar_formasi
];
```

**Penjelasan:**
- Menambahkan field `status_formasi` ke mass assignable fields
- Memungkinkan fill data saat create/update pegawai

---

### 2.2 File: `app/Models/Formasijabatan.php`

**Perubahan 1 - Sisa bisa minus:**
```php
// SEBELUM (return max 0):
public function getSisaAttribute(): int
{
    $kuota = (int) ($this->kuota ?? 0);
    $terisi = (int) ($this->getTerisiAttribute() ?? 0);
    return max($kuota - $terisi, 0);  // ❌ Tidak bisa minus
}

// SESUDAH (bisa minus):
public function getSisaAttribute(): int
{
    $kuota = (int) ($this->kuota ?? 0);
    $terisi = (int) ($this->getTerisiAttribute() ?? 0);
    return $kuota - $terisi;  // ✅ Bisa minus (over kuota)
}
```

**Perubahan 2 - Helper untuk CSS class:**
```php
public function getSisaClassAttribute(): string
{
    $sisa = $this->sisa;

    if ($sisa < 0) {
        return 'text-danger fw-bold'; // Merah bold untuk over kuota
    } elseif ($sisa === 0) {
        return 'text-warning fw-bold'; // Kuning bold untuk penuh
    } else {
        return ''; // Normal
    }
}
```

**Penjelasan:**
- `getSisaAttribute()` sekarang mengembalikan nilai NEGATIF jika over kuota
- `getSisaClassAttribute()` helper untuk mendapatkan class CSS berdasarkan nilai sisa

---

## 3. File Controllers (Dimodifikasi)

### 3.1 File: `app/Http/Controllers/SdmController.php`

**Perubahan yang dilakukan:**

#### A. Method `index()` - Menambahkan Filter Status Formasi

```php
public function index()
{
    $filterStatus = request()->get('filter_status', ''); // Filter status formasi

    $sdm = Sdmmodels::with([...])
        ->when($filterStatus, function($q) use ($filterStatus) {
            if ($filterStatus === 'terpenuhi') {
                return $q->where('status_formasi', 'terpenuhi');
            } elseif ($filterStatus === 'di_luar_formasi') {
                return $q->where('status_formasi', 'di_luar_formasi');
            }
        })
        ->orderByDesc('created_at')
        ->get();

    return view('sdm.index', compact('sdm', 'filterStatus'));
}
```

#### B. Method `store()` - Soft Warning & Set Status Formasi

```php
// Cek status formasi SEBELUM pegawai ditambahkan
$statusFormasi = 'terpenuhi'; // default
$warningMessage = null;

if (!empty($formasiJabatanId)) {
    $formasi = Formasijabatan::find($formasiJabatanId);
    if ($formasi) {
        $terisi = $formasi->sdmAktif()->count();
        $sisa = $formasi->kuota - $terisi;

        if ($sisa <= 0) {
            $statusFormasi = 'di_luar_formasi';
            $warningMessage = "Peringatan: Formasi '{$formasi->nama_formasi}' sudah melebihi kuota (sisa: {$sisa}). Pegawai akan ditandai sebagai 'Di Luar Formasi'.";
        }
    }
}

// Set status_formasi saat create
Sdmmodels::create([
    // ... other fields ...
    'status_formasi' => $statusFormasi,
]);

// Recalculate status untuk pegawai lain di formasi yang sama
if (!empty($formasiJabatanId)) {
    $this->recalculateStatusFormasi($formasiJabatanId);
}
```

#### C. Method `update()` - Recalculate saat Pindah Formasi

```php
// Simpan formasi lama untuk recalculate nanti
$oldFormasiId = $sdm->formasi_jabatan_id;

// Cek status formasi baru (SAMA dengan store)
// ... (logic cek kuota sama seperti store) ...

$sdm->update([
    // ... other fields ...
    'status_formasi' => $statusFormasi,
]);

// Recalculate status untuk formasi lama (jika berubah)
if ($oldFormasiId && $oldFormasiId != $formasiJabatanId) {
    $this->recalculateStatusFormasi($oldFormasiId);
}

// Recalculate status untuk formasi baru
if (!empty($formasiJabatanId)) {
    $this->recalculateStatusFormasi($formasiJabatanId);
}
```

#### D. Method `destroy()` - Recalculate saat Hapus

```php
// Simpan formasi_id untuk recalculate setelah hapus
$formasiId = $sdm->formasi_jabatan_id;

$sdm->delete();

// Recalculate status untuk pegawai lain di formasi yang sama
if ($formasiId) {
    $this->recalculateStatusFormasi($formasiId);
}
```

#### E. Method `restore()` - Recalculate saat Restore

```php
$sdm->restore();

// Recalculate status setelah restore
if ($sdm->formasi_jabatan_id) {
    $this->recalculateStatusFormasi($sdm->formasi_jabatan_id);
}
```

#### F. Method `forceDelete()` - Recalculate saat Force Delete

```php
$formasiId = $sdm->formasi_jabatan_id;

$sdm->forceDelete();

// Recalculate status setelah force delete
if ($formasiId) {
    $this->recalculateStatusFormasi($formasiId);
}
```

#### G. Private Method `recalculateStatusFormasi()` - Baru

**Logic Recalculate:**
1. Ambil semua SDM aktif dalam formasi tertentu
2. Urutkan berdasarkan `created_at ASC` (yang pertama masuk = prioritas)
3. SDM sejumlah kuota = status `'terpenuhi'`
4. Sisa SDM (melebihi kuota) = status `'di_luar_formasi'`

```php
/**
 * Recalculate status_formasi untuk semua SDM dalam formasi tertentu
 *
 * Logic:
 * - SDM diurutkan berdasarkan created_at (yang pertama masuk = prioritas)
 * - Hitung kuota formasi
 * - SDM sejumlah kuota = status 'terpenuhi'
 * - Sisa SDM = status 'di_luar_formasi'
 *
 * @param int $formasiJabatanId
 * @return void
 */
private function recalculateStatusFormasi($formasiJabatanId): void
{
    $formasi = Formasijabatan::find($formasiJabatanId);
    if (!$formasi) {
        return;
    }

    // Ambil semua SDM aktif dalam formasi ini, urut berdasarkan created_at ASC
    $allSdm = Sdmmodels::where('formasi_jabatan_id', $formasiJabatanId)
        ->where('aktif', true)
        ->orderBy('created_at', 'asc')
        ->get();

    $kuota = (int) $formasi->kuota;
    $count = 0;

    foreach ($allSdm as $sdm) {
        $count++;

        // SDM sejumlah kuota pertama = terpenuhi
        // Sisa SDM = di_luar_formasi
        $newStatus = ($count <= $kuota) ? 'terpenuhi' : 'di_luar_formasi';

        // Hanya update jika status berubah (untuk efisiensi)
        if ($sdm->status_formasi !== $newStatus) {
            $sdm->update(['status_formasi' => $newStatus]);
        }
    }
}
```

**Catatan Penting:**
- Method ini bersifat **private** dan reusable
- Dipanggil otomatis saat: store, update (pindah formasi), destroy, restore, forceDelete
- Hanya update jika status berubah (efisiensi database)

---

## 4. File Views (Dimodifikasi)

### 4.1 File: `resources/views/formasi_jabatan/index.blade.php`

**Perubahan:** Warna Sisa (Minus/Zero)

```blade
{{-- SISA --}}
@foreach($cols as $c)
  <td @class([
    'border-start-thick'=>$loop->first,
    'text-danger fw-bold'=>$s[$c] < 0,     // Merah bold jika minus
    'text-warning fw-bold'=>$s[$c] == 0     // Kuning bold jika nol
  ])>
    {{ $s[$c] }}
  </td>
@endforeach
<td @class([
  'text-danger fw-bold'=>$sTotal < 0,
  'text-warning fw-bold'=>$sTotal == 0
])>
  <b>{{ $sTotal }}</b>
</td>
```

**Tampilan:**
- Sisa < 0 (over kuota): **Merah Bold**
- Sisa = 0 (penuh): **Kuning Bold**
- Sisa > 0 (ada kuota): Normal

---

### 4.2 File: `resources/views/sdm/index.blade.php`

**Perubahan 1 - Filter Dropdown:**
```blade
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Data Pemangku JFT</h4>
  <div class="d-flex gap-2">
    {{-- Filter Status Formasi --}}
    <select id="filterStatusFormasi" class="form-select form-select-sm" style="width: 200px;">
      <option value="">Semua Status Formasi</option>
      <option value="terpenuhi" {{ $filterStatus === 'terpenuhi' ? 'selected' : '' }}>Terpenuhi</option>
      <option value="di_luar_formasi" {{ $filterStatus === 'di_luar_formasi' ? 'selected' : '' }}>Di Luar Formasi</option>
    </select>
    @can('create pegawai')
    <a href="{{ route('user.sdm.create') }}" class="btn btn-primary btn-sm">+ Tambah Pemangku JFT</a>
    <a href="{{ route('user.sdm.import.form') }}" class="btn btn-success btn-sm">+ Import Excel</a>
    @endcan
  </div>
</div>
```

**Perubahan 2 - Kolom Status Formasi:**
```blade
{{-- Header Table --}}
<th>Status Formasi</th>

{{-- Body Table --}}
<td>
  @if($row->formasi_jabatan_id)
    @if($row->status_formasi === 'di_luar_formasi')
      <span class="badge bg-danger">Di Luar Formasi</span>
    @else
      <span class="badge bg-success">Terpenuhi</span>
    @endif
  @else
    <span class="text-muted">-</span>
  @endif
</td>
```

**Perubahan 3 - JavaScript untuk Filter:**
```javascript
// Handle Filter Status Formasi
$('#filterStatusFormasi').on('change', function() {
    const status = $(this).val();
    const url = new URL(window.location);

    if (status === '') {
        url.searchParams.delete('filter_status');
    } else {
        url.searchParams.set('filter_status', status);
    }

    window.location.href = url.toString();
});
```

---

### 4.3 File: `resources/views/layouts/component/alert.blade.php`

**Perubahan:** Menambahkan Alert untuk Warning Message

```blade
@if(session('success'))
<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-check"></i> Sukses!</h5>
  {{ session('success') }}
</div>
@endif

@if(session('warning'))
{{-- BARU: Warning Alert --}}
<div class="alert alert-warning alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan!</h5>
  {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-ban"></i> Error!</h5>
  {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-ban"></i> Error Validasi!</h5>
  <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif
```

---

## 5. Perintah Artisan untuk Setup

### Jalankan Migration
```bash
php artisan migrate
```

### Rollback (jika perlu)
```bash
php artisan migrate:rollback
```

### Refresh Migration (jika ada masalah)
```bash
php artisan migrate:refresh --seed
```

---

## 6. Alur Logika Status Formasi

### Saat Tambah Pegawai (store)

```
1. User isi form pegawai
   ↓
2. Cek formasi jabatan yang dipilih
   ↓
3. Hitung sisa kuota: sisa = kuota - terisi
   ↓
4. Jika sisa <= 0
   - Set status_formasi = 'di_luar_formasi'
   - Tampilkan warning message
   ↓
5. Jika sisa > 0
   - Set status_formasi = 'terpenuhi'
   ↓
6. Simpan pegawai
   ↓
7. Recalculate status untuk SEMUA pegawai di formasi yang sama
   (yang pertama masuk = terpenuhi, sisanya = di_luar_formasi)
```

### Saat Edit Pegawai (update)

```
1. Simpan formasi lama
   ↓
2. User edit data pegawai (mungkin pindah formasi)
   ↓
3. Cek status formasi baru (sama seperti store)
   ↓
4. Update pegawai
   ↓
5. Recalculate status untuk formasi LAMA (jika berubah)
   ↓
6. Recalculate status untuk formasi BARU
```

### Saat Hapus Pegawai (destroy)

```
1. Simpan formasi_id
   ↓
2. Hapus pegawai (soft delete)
   ↓
3. Recalculate status untuk pegawai lain di formasi yang sama
   (kuota menjadi lebih longgar, prioritas berdasarkan created_at)
```

### Saat Restore Pegawai

```
1. Restore pegawai dari trash
   ↓
2. Recalculate status untuk formasi yang sama
```

---

## 7. Skema Recalculate Status Formasi

### Prioritas Berdasarkan Waktu Masuk

**Aturan:**
- SDM diurutkan berdasarkan `created_at ASC` (yang paling awal masuk = prioritas tertinggi)
- SDM ke-1 sampai ke-K (K = kuota) → status `'terpenuhi'`
- SDM ke-(K+1) sampai selanjutnya → status `'di_luar_formasi'`

**Contoh:**

| created_at | Nama | Urutan | Kuota Formasi | Status Formasi |
|------------|------|--------|---------------|----------------|
| 2025-01-01 | Ahmad | 1 | 5 | ✅ Terpenuhi |
| 2025-01-02 | Budi | 2 | 5 | ✅ Terpenuhi |
| 2025-01-03 | Citra | 3 | 5 | ✅ Terpenuhi |
| 2025-01-04 | Doni | 4 | 5 | ✅ Terpenuhi |
| 2025-01-05 | Eka | 5 | 5 | ✅ Terpenuhi |
| 2025-01-06 | Fajar | 6 | 5 | ❌ Di Luar Formasi |
| 2025-01-07 | Gita | 7 | 5 | ❌ Di Luar Formasi |

**Setelah Fajar dihapus:**

| created_at | Nama | Urutan Baru | Status Formasi |
|------------|------|-------------|----------------|
| 2025-01-01 | Ahmad | 1 | ✅ Terpenuhi |
| 2025-01-02 | Budi | 2 | ✅ Terpenuhi |
| 2025-01-03 | Citra | 3 | ✅ Terpenuhi |
| 2025-01-04 | Doni | 4 | ✅ Terpenuhi |
| 2025-01-05 | Eka | 5 | ✅ Terpenuhi |
| 2025-01-06 | Gita | 6 | ✅ Terpenuhi ← Berubah! |

---

## 8. Contoh Use Case

### Use Case 1: Tambah Pegawai Saat Kuota Penuh

**Kondisi Awal:**
- Formasi: Pengawas Keselamatan Pelayaran
- Unit Kerja: Kantor KSOP Tanjung Priok
- Kuota: 5
- Terisi: 5 (Ahmad, Budi, Citra, Doni, Eka)
- Sisa: 0

**Aksi:**
- User tambah pegawai baru: "Fajar"

**Proses:**
1. Sistem cek sisa = 5 - 5 = 0
2. Karena sisa <= 0, set `status_formasi = 'di_luar_formasi'`
3. Tampilkan warning: "Peringatan: Formasi sudah melebihi kuota (sisa: 0). Pegawai akan ditandai sebagai 'Di Luar Formasi'."
4. Simpan pegawai
5. Recalculate untuk SEMUA pegawai di formasi ini:
   - Ahmad, Budi, Citra, Doni, Eka → 'terpenuhi' (urutan 1-5)
   - Fajar → 'di_luar_formasi' (urutan 6)

**Hasil:**
- Pegawai berhasil ditambahkan
- Fajar berstatus "Di Luar Formasi"
- Tabel formasi menunjukkan sisa = -1 (merah bold)

---

### Use Case 2: Hapus Pegawai

**Kondisi Awal:**
- Kuota: 5
- Terisi: 6 (over kuota 1)
- Fajar → 'di_luar_formasi'

**Aksi:**
- User hapus "Eka" (urutan ke-5)

**Proses:**
1. Hapus Eka
2. Recalculate untuk formasi ini:
   - Ahmad, Budi, Citra, Doni → 'terpenuhi' (urutan 1-4)
   - Fajar → 'terpenuhi' (urutan 5, sekarang dalam kuota) ← Berubah!

**Hasil:**
- Eka dihapus
- Fajar berubah dari "Di Luar Formasi" → "Terpenuhi"
- Tabel formasi menunjukkan sisa = 0 (kuning bold, penuh)

---

### Use Case 3: Pindah Pegawai ke Formasi Lain

**Kondisi Awal:**
- Formasi A: Kuota 3, Terisi 3 (Penuh)
- Formasi B: Kuota 5, Terisi 2 (Masih ada 3 kuota)

**Aksi:**
- User edit "Doni" dari Formasi A → Formasi B

**Proses:**
1. Simpan formasi lama: Formasi A
2. Update Doni ke Formasi B
3. Recalculate untuk Formasi A (berkurang 1 orang)
4. Recalculate untuk Formasi B (bertambah 1 orang)

**Hasil:**
- Doni pindah ke Formasi B dengan status 'terpenuhi'
- Formasi A: Sisa = +1 (masih ada kuota)
- Formasi B: Sisa = +2 (masih ada kuota)

---

## 9. Testing Checklist

### Test 1: Tambah Pegawai Normal (Kuota Masih Ada)

- [ ] Login sebagai admin/operator
- [ ] Buka menu Pegawai JFT
- [ ] Klik "+ Tambah Pemangku JFT"
- [ ] Pilih formasi yang masih ada kuota
- [ ] Isi data lengkap
- [ ] Simpan
- [ ] ✅ Pegawai berhasil ditambahkan
- [ ] ✅ Status Formasi = "Terpenuhi" (badge hijau)

### Test 2: Tambah Pegawai Over Kuota

- [ ] Pilih formasi yang SUDAH PENUH
- [ ] Isi data lengkap
- [ ] Simpan
- [ ] ✅ Pegawai berhasil ditambahkan (TIDAK diblokir)
- [ ] ✅ Muncul warning message
- [ ] ✅ Status Formasi = "Di Luar Formasi" (badge merah)
- [ ] Cek halaman Formasi → Sisa = negatif (merah bold)

### Test 3: Filter Status Formasi

- [ ] Buka halaman Pegawai JFT
- [ ] Filter: "Semua Status Formasi" → Tampilkan semua
- [ ] Filter: "Terpenuhi" → Hanya pegawai dengan formasi valid
- [ ] Filter: "Di Luar Formasi" → Hanya pegawai over kuota

### Test 4: Hapus Pegawai

- [ ] Hapus pegawai yang statusnya "Di Luar Formasi"
- [ ] ✅ Pegawai berhasil dihapus
- [ ] ✅ Pegawai lain di formasi yang sama direcalculate
- [ ] Cek apakah ada pegawai yang berubah dari "Di Luar Formasi" → "Terpenuhi"

### Test 5: Warna Sisa di Formasi

- [ ] Buka halaman Formasi
- [ ] Cek tabel sisa:
  - [ ] Sisa > 0 → Teks normal
  - [ ] Sisa = 0 → Kuning bold
  - [ ] Sisa < 0 → Merah bold

---

## 10. Troubleshooting

### Masalah 1: Warning Message Tidak Muncul

**Gejala:** Tambah pegawai over kuota tapi tidak ada warning

**Solusi:**
- Cek file `layouts/component/alert.blade.php`
- Pastikan ada bagian `@if(session('warning'))`
- Clear cache: `php artisan view:clear`

---

### Masalah 2: Status Formasi Tidak Berubah

**Gejala:** Setelah hapus pegawai, status pegawai lain tidak berubah

**Solusi:**
- Pastikan method `recalculateStatusFormasi()` dipanggil di `destroy()`
- Cek apakah pegawai yang dihapus punya `formasi_jabatan_id`
- Cek log Laravel: `storage/logs/laravel.log`

---

### Masalah 3: Filter Tidak Berfungsi

**Gejala:** Klik filter tidak memfilter data

**Solusi:**
- Pastikan JavaScript di-include dengan benar
- Cek console browser untuk error JavaScript
- Clear cache: `php artisan route:clear`

---

### Masalah 4: Sisa Tetap Tidak Bisa Minus

**Gejala:** Sisa selalu 0 jika over kuota

**Solusi:**
- Cek model `Formasijabatan.php`
- Pastikan `getSisaAttribute()` TIDAK menggunakan `max(0, ...)`
- Run migration ulang: `php artisan migrate:refresh`

---

## 11. Query SQL untuk Manual Check

### Cek Status Formasi per Pegawai
```sql
SELECT
    s.nama_lengkap,
    f.nama_formasi,
    f.kuota,
    COUNT(CASE WHEN s2.aktif = 1 THEN 1 END) as terisi,
    (f.kuota - COUNT(CASE WHEN s2.aktif = 1 THEN 1 END)) as sisa,
    s.status_formasi
FROM sumber_daya_manusia s
LEFT JOIN formasi_jabatan f ON s.formasi_jabatan_id = f.id
LEFT JOIN sumber_daya_manusia s2 ON s2.formasi_jabatan_id = f.id AND s2.aktif = 1
WHERE s.formasi_jabatan_id = 1 -- ganti dengan ID formasi yang dicek
GROUP BY s.id;
```

### Cek Distribusi Status Formasi
```sql
SELECT
    status_formasi,
    COUNT(*) as jumlah
FROM sumber_daya_manusia
WHERE formasi_jabatan_id IS NOT NULL
GROUP BY status_formasi;
```

---

## 12. Catatan Penting

### ⚠️ Peringatan

1. **Jangan mengubah urutan created_at** - Urutan ini menentukan prioritas status formasi
2. **Recalculate otomatis** - Tidak perlu update status manual, sistem otomatis menghitung
3. **Soft delete** - Pegawai yang dihapus (soft delete) TIDAK dihitung dalam recalculate
4. **Hanya pegawai aktif** - Yang dihitung hanya pegawai dengan `aktif = true`

### 💡 Tips

1. **Filter "Di Luar Formasi"** berguna untuk melihat pegawai yang melebihi kuota
2. **Warna merah bold** pada sisa formasi memudahkan identifikasi over kuota
3. **Recalculate efisien** - Hanya update jika status berubah

---

## 13. Future Enhancements

### Recommended Updates
1. **Export Excel dengan Status Formasi** - Tambahkan kolom status di export
2. **Notifikasi Email** - Kirim email ke admin jika ada formasi over kuota
3. **Dashboard Widget** - Widget di dashboard untuk menampilkan jumlah pegawai di luar formasi
4. **Audit Trail** - Log perubahan status_formasi untuk tracking

---

## 14. Changelog & Update Terbaru

### Versi 1.3.0 - 10 Maret 2026 (Sesi Kedua)

#### Perubahan yang Dilakukan:

#### 14.1 Filter Kota/Kabupaten untuk Unit Kerja (Dinamis)

**Masalah:** Dropdown unit kerja menampilkan semua unit kerja meskipun sudah memfilter kota/kabupaten.

**Solusi:** Menambahkan filter dinamis pada dropdown unit kerja berdasarkan kota/kabupaten yang dipilih.

**File yang diubah:** `resources/views/formasi_jabatan/index.blade.php`

**Perubahan:**
1. Menambahkan `id="unitFilter"` pada dropdown unit kerja
2. Menambahkan `data-regency` attribute pada setiap option unit kerja
3. Menambahkan fungsi JavaScript `filterUnitsByRegency()` untuk memfilter unit kerja secara real-time

**Snippet Kode:**
```blade
{{-- Dropdown unit kerja dengan data-regency --}}
<select name="unit_kerja_id" id="unitFilter" class="form-select">
  <option value="">Semua Unit Kerja</option>
  @foreach($units as $u)
    <option value="{{ $u->no_rs }}" data-regency="{{ $u->regency_id ?? '' }}"
            @selected(($filter['unit_kerja_id']??'')==$u->no_rs)>
      {{ $u->nama_rumahsakit }}
    </option>
  @endforeach
</select>
```

**JavaScript:**
```javascript
// Filter unit kerja berdasarkan regency yang dipilih
function filterUnitsByRegency(regencyId){
  $unit.innerHTML = '<option value="">Semua Unit Kerja</option>';

  const filtered = regencyId
    ? allUnitOptions.filter(opt => opt.dataset.regency === String(regencyId))
    : allUnitOptions;

  filtered.forEach(opt => {
    $unit.appendChild(opt.cloneNode(true));
  });
}

// Event listener saat regency berubah
$reg?.addEventListener('change', e => {
  filterUnitsByRegency(e.target.value);
});
```

**Hasil:**
- Ketika user memilih kota/kabupaten, dropdown unit kerja otomatis hanya menampilkan unit kerja di kota tersebut
- Tidak perlu menekan tombol "Terapkan" terlebih dahulu

---

#### 14.2 Tombol Edit Selalu Muncul di Halaman Formasi

**Masalah:** Tombol "Edit Grup" hanya muncul ketika user memilih filter Unit Kerja dan Tahun.

**Solusi:** Mengubah tombol menjadi button dengan JavaScript validation, sehingga selalu muncul namun memvalidasi filter saat diklik.

**File yang diubah:** `resources/views/formasi_jabatan/index.blade.php`

**Perubahan:**
```blade
@can('edit formasi')
<div class="mb-3">
  <button id="btn-edit-group" type="button" class="btn btn-warning">
    Edit Grup: Unit & Tahun Terpilih
  </button>
</div>
@endcan
```

**JavaScript:**
```javascript
document.getElementById('btn-edit-group')?.addEventListener('click', function(){
  const unit  = document.querySelector('select[name="unit_kerja_id"]').value;
  const tahun = document.querySelector('select[name="tahun"]').value;
  if (!unit || !tahun) {
    alert('Pilih Unit Kerja dan Tahun terlebih dahulu untuk mengedit formasi.');
    return;
  }
  const url = @json(route('user.formasi.edit-group')) + '?unit=' + encodeURIComponent(unit) + '&tahun=' + encodeURIComponent(tahun);
  window.location.href = url;
});
```

**Hasil:**
- Tombol Edit selalu visible
- Saat diklik tanpa filter → muncul alert
- Saat diklik dengan filter → langsung ke halaman edit

---

#### 14.3 Tombol Edit & Hapus per Unit Kerja

**Masalah:** User harus menggunakan filter untuk mengedit formasi unit kerja tertentu.

**Solusi:** Menambahkan tombol Edit dan Hapus langsung di setiap card/tabel unit kerja.

**File yang diubah:**
1. `app/Http/Controllers/FormasiJabatanController.php`
2. `resources/views/formasi_jabatan/index.blade.php`
3. `routes/web.php`

**Perubahan Controller:**
```php
// Tambahkan metadata ke struktur $table
$table[$unitName]['_meta'] = [
    'unit_kerja_id' => $f->unit_kerja_id,
    'tahuns' => [],
];
```

**Perubahan View:**
```blade
@forelse($table as $unitName => $rows)
  @php
    $meta = $rows['_meta'] ?? [];
    $unitId = $meta['unit_kerja_id'] ?? null;
    $editTahun = $filter['tahun'] ?? ($meta['tahuns'][0] ?? null);
  @endphp
  <div class="card mb-4">
    <div class="card-body p-0">
      <div class="row g-0">
        {{-- Tabel --}}
        <div class="col-md">
          <div class="table-responsive">
            <table>...</table>
          </div>
        </div>

        {{-- Tombol Actions di sebelah kanan --}}
        <div class="col-auto formasi-actions-col d-flex flex-column justify-content-start gap-2 p-3 border-start bg-light">
          @can('edit formasi')
          @if($unitId && $editTahun)
          <a href="{{ route('user.formasi.edit-group', ['unit' => $unitId, 'tahun' => $editTahun]) }}"
             class="btn btn-warning btn-sm" title="Edit Formasi">
             <i class="fas fa-edit"></i>
          </a>
          @endif
          @endcan

          @can('delete formasi')
          @if($unitId)
          <button type="button"
                  class="btn btn-danger btn-sm"
                  data-unit-id="{{ $unitId }}"
                  data-unit-name="{{ $unitName }}"
                  data-tahun="{{ $editTahun }}"
                  onclick="confirmDeleteUnitFormasi(this)"
                  title="Hapus Formasi">
            <i class="fas fa-trash"></i>
          </button>
          @endif
          @endcan
        </div>
      </div>
    </div>
  </div>
@endforelse
```

**CSS:**
```css
/* Tombol actions di sebelah kanan table */
.formasi-actions-col {
  min-width: 60px;
}
.formasi-actions-col .btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
```

**Route Baru:**
```php
// Hapus formasi per unit & tahun
Route::delete('formasi/delete-group', [\App\Http\Controllers\FormasiJabatanController::class, 'deleteGroup'])
    ->middleware('permission:delete formasi')
    ->name('formasi.delete-group');
```

**Controller Method Baru:**
```php
public function deleteGroup(Request $request)
{
    $unitId = $request->query('unit');
    $tahun  = $request->query('tahun');

    $query = Formasijabatan::where('unit_kerja_id', $unitId);

    if ($tahun) {
        $query->where('tahun_formasi', $tahun);
        $message = "Semua formasi untuk tahun {$tahun} berhasil dihapus.";
    } else {
        $message = "Semua formasi (semua tahun) berhasil dihapus.";
    }

    $count = $query->count();
    if ($count === 0) {
        return redirect()->route('user.formasi.index')->with('error', 'Tidak ada data formasi yang ditemukan.');
    }

    $query->delete();

    return redirect()->route('user.formasi.index')->with('success', "{$count} data formasi berhasil dihapus. {$message}");
}
```

**JavaScript untuk Konfirmasi Hapus:**
```javascript
function confirmDeleteUnitFormasi(btn) {
  const unitId = btn.dataset.unitId;
  const unitName = btn.dataset.unitName;
  const tahun = btn.dataset.tahun;

  let message = tahun
    ? `Apakah Anda yakin ingin menghapus SEMUA data formasi untuk:\n\nUnit Kerja: ${unitName}\nTahun: ${tahun}\n\nData yang dihapus tidak dapat dikembalikan!`
    : `Apakah Anda yakin ingin menghapus SEMUA data formasi untuk:\n\nUnit Kerja: ${unitName}\nSemua Tahun\n\nData yang dihapus tidak dapat dikembalikan!`;

  if (!confirm(message)) {
    return;
  }

  // Submit form dengan method DELETE
  let url = @json(route('user.formasi.delete-group')) + '?unit=' + encodeURIComponent(unitId);
  if (tahun) {
    url += '&tahun=' + encodeURIComponent(tahun);
  }

  // Buat form dan submit
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = url;

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;

  const methodInput = document.createElement('input');
  methodInput.type = 'hidden';
  methodInput.name = '_method';
  methodInput.value = 'DELETE';

  form.appendChild(csrfInput);
  form.appendChild(methodInput);
  document.body.appendChild(form);
  form.submit();
}
```

**Hasil:**
- Setiap card unit kerja memiliki tombol Edit dan Hapus di sebelah kanan
- Tombol Edit → langsung ke halaman edit untuk unit & tahun tersebut
- Tombol Hapus → konfirmasi → hapus semua formasi di unit & tahun tersebut
- Layout lebih bersih dan user-friendly

---

#### 14.4 Perbaikan Error "undefined array key kuota"

**Masalah:** Setelah menambahkan metadata `_meta` ke struktur `$table`, terjadi error "undefined array key kuota" saat looping.

**Solusi:** Melewati (skip) key `_meta` saat looping di view.

**File yang diubah:** `resources/views/formasi_jabatan/index.blade.php`

**Perubahan:**
```blade
<tbody>
  @php $i=1; @endphp
  @foreach($rows as $key => $row)
    @if($key === '_meta') @continue @endif
    @php
      $k = $row['kuota'];  $t = $row['terisi'];  $s = $row['sisa'];
      $kTotal = array_sum($k); $tTotal = array_sum($t); $sTotal = array_sum($s);
    @endphp
    <!-- ... -->
  @endforeach
</tbody>
```

**Penjelasan:**
- Key `_meta` berisi metadata unit kerja (unit_kerja_id, tahuns)
- Key ini tidak memiliki struktur data yang sama dengan row biasa (kuota, terisi, sisa)
- `@if($key === '_meta') @continue @endif` melewati key tersebut saat looping

---

### Ringkasan Perubahan Versi 1.3.0

| No | Fitur | File yang Diubah | Jenis Perubahan |
|---|-------|------------------|-----------------|
| 1 | Filter kota untuk unit kerja dinamis | `formasi_jabatan/index.blade.php` | Frontend/JavaScript |
| 2 | Tombol Edit selalu muncul | `formasi_jabatan/index.blade.php` | Frontend/JavaScript |
| 3 | Tombol Edit per unit kerja | `formasi_jabatan/index.blade.php`, `FormasiJabatanController.php`, `web.php` | Full-stack |
| 4 | Tombol Hapus per unit kerja | `formasi_jabatan/index.blade.php`, `FormasiJabatanController.php`, `web.php` | Full-stack |
| 5 | Perbaikan error undefined key | `formasi_jabatan/index.blade.php` | Bug Fix |
| 6 | Layout tombol actions di kanan tabel | `formasi_jabatan/index.blade.php` | UI/UX |

**Total File Dimodifikasi:** 3
**Total File Baru:** 0
**Total Routes Baru:** 1 (`user.formasi.delete-group`)
**Total Controller Methods Baru:** 1 (`deleteGroup`)

---

---

**Dokumentasi ini dibuat pada:** 10 Maret 2026
**Versi Dokumentasi:** 1.3.0
**Update Terakhir:** 10 Maret 2026 (Sesi Kedua)
**Penulis:** Claude Code (AI Assistant)

---

*End of Documentation*
