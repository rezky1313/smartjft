# CHANGELOG - SMART JFT

## Catatan Konvensi Git
- **Jangan** tambahkan `Co-Authored-By: Claude` di commit message (per instruksi user, 24 Juni 2026)

---

## Versi 1.12.2 - Bug Fix: Card "Perlu Tindakan" Dashboard Kosong
**Tanggal:** 8 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Card "Perlu Tindakan" di dashboard Super Admin/Admin selalu menampilkan angka 0 untuk jadwal ujikom aktif, dan tidak pernah menampilkan data Pengangkatan JFT sama sekali. Dashboard Admin Unit juga terdampak bug enum yang sama di daftar jadwal aktifnya.

---

## Perubahan

### Controller
- **UBAH** `app/Http/Controllers/PetaDashboardController.php`
  - Tambah `use App\Models\PengangkatanPermohonan;`
  - `$perluTindakan['jadwal_aktif']`: query salah pakai `UjikomJadwal::where('status', 'dipublikasikan')` — enum yang benar di database adalah `'published'` (konsisten dengan sidebar, `UjikomOnlineController`, dll). Query ini **tidak pernah cocok** sehingga selalu menghasilkan 0
  - `dashboardAdminUnit()`: bug enum yang identik pada `$jadwalAktif` (daftar jadwal aktif Admin Unit) — diperbaiki jadi `'published'`
  - **BARU** `$perluTindakan['permohonan_pengangkatan_pending']` — `PengangkatanPermohonan::where('status', 'diajukan')->count()`, sebelumnya modul Pengangkatan JFT tidak pernah dimasukkan ke card ini sejak dibuat

### View
- **UBAH** `resources/views/users/dashboard.blade.php`
  - Tambah card ke-6 di section "Perlu Tindakan": "Permohonan Pengangkatan Menunggu", link ke `pengangkatan.index?status=diajukan`

---

## Catatan Teknis

- **Bukan bug:** sempat dicurigai `unit_kerja_id` tidak konsisten antar tabel (`users`, `ujikom_pendaftaran`, `pengangkatan_permohonan`) untuk dashboard Admin Unit — dicek silang dan semuanya sudah konsisten memakai `no_rs` yang sama. Angka 0 yang terlihat sebelumnya untuk sebagian metrik memang valid (data uji saat itu belum ada yang berstatus "menunggu")
- Dashboard Admin Unit (`dashboardAdminUnit()`) **tidak** punya card setara "Perlu Tindakan" — metriknya terpisah (`permohonanMenunggu`/`Diproses`/`Selesai` + `jadwalAktif`) dan tidak menyertakan data Pengangkatan JFT sama sekali. Di luar cakupan perbaikan ini karena tidak diminta, dicatat untuk referensi jika suatu saat perlu ditambahkan

---

## Versi 1.12.1 - Bug Fix: Bank Soal, Paket Ujian, Ujikom Online, Dashboard Pemangku
**Tanggal:** 6 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Serangkaian perbaikan bug ditemukan saat pengujian pasca-rombak v1.9.0–v1.12.0: crash import Bank Soal, menu Paket Ujian hilang dari sidebar, tampilan kategori soal rusak, error relasi model di dashboard pemangku, sesi ujian online gagal dimulai untuk peserta yang sudah dibuka admin, dan potensi satu jadwal ujikom punya lebih dari satu paket aktif sekaligus.

---

## Perubahan

### 1. Fix Import Bank Soal — `SkipsErrors` dipakai sebagai interface, padahal trait
- **UBAH** `app/Imports/BankSoalImport.php`
  - `class BankSoalImport implements ToCollection, WithHeadingRow, SkipsOnError, SkipsErrors` → `SkipsErrors` dipindah jadi `use SkipsErrors;` di dalam class body

### 2. Menu Paket Ujian hilang dari sidebar
- **UBAH** `resources/views/layouts/users/master.blade.php`
  - Tambah item sidebar "Paket Ujian" (`route('paket-ujian.index')`) tepat setelah "Kategori Soal", dalam blok `@hasanyrole('admin|super_admin')` yang sama

### 3. Bank Soal — 6 perbaikan sekaligus
- **BARU** `database/migrations/2026_07_06_000001_update_soal_kategori_add_klasifikasi.php`
  - Enum `matra` di `soal_kategori` tambah nilai `perkeretaapian` (raw `DB::statement` ALTER TABLE — `doctrine/dbal` tidak terinstall, `->change()` tidak bisa dipakai)
  - Tambah kolom `klasifikasi` enum (`keahlian`/`keterampilan`/`umum`), default `umum`, setelah `matra`
- **UBAH** `app/Models/SoalKategori.php`
  - Tambah `klasifikasi` ke `$fillable`
  - `getLabelMatraAttribute()`: tambah label `perkeretaapian` → "Perkeretaapian"
  - **BARU** `getLabelKlasifikasiAttribute()`
- **UBAH** `app/Imports/BankSoalImport.php`
  - Tambah `implements WithMultipleSheets` + method `sheets()` — hanya baca sheet bernama "Data Soal" saat import Excel
- **UBAH** `resources/views/bank_soal/index.blade.php`
  - Fix bug tampilan: fallback nama kategori kosong sebelumnya berupa string HTML mentah (`'<span class="text-muted">—</span>'`) yang di-escape oleh `{{ }}` sehingga tampil sebagai teks tag literal di layar → diganti fallback `'Umum'` (plain text)
  - Tombol "Aktifkan" di kolom Aksi kini juga muncul untuk soal berstatus `nonaktif` (sebelumnya hanya `draft`)
- **UBAH** `resources/views/bank_soal/edit.blade.php`
  - Tambah field **Status** (draft/aktif/nonaktif) — pakai `form-control` (Bootstrap 4, bukan `form-select`). Field Kategori tidak diubah karena sudah editable sebelumnya
- **UBAH** `app/Http/Controllers/BankSoalController.php`
  - `update()`: validasi & simpan `status`
  - `approve()`: guard diubah dari hanya `draft` menjadi `draft` **atau** `nonaktif`
- **BARU** `database/seeders/SoalKategoriLengkapSeeder.php`
  - 97 kategori (24 jabatan fungsional × jenjang keterampilan/keahlian + 1 "Umum") via `updateOrCreate` — tidak menghapus data lama
  - **Catatan:** total akhir di database jadi **106**, bukan 97, karena 10 kategori dari `SoalKategoriSeeder` lama (nama berbeda, mis. "PKB Terampil - Darat") tidak terhapus sesuai instruksi "jangan hapus data lama"

### 4. Fix relasi `formasiJabatan` hilang di `Sdmmodels`
- **UBAH** `app/Models/Sdmmodels.php`
  - Tambah relasi `formasiJabatan()` (alias `belongsTo(Formasijabatan::class, 'formasi_jabatan_id')`) — relasi lama `formasi()` tidak pernah dipakai; 3 tempat lain (`dashboard.blade.php`, `PengangkatanPermohonan::generateKandidat()`) sudah memanggil nama `formasiJabatan`, jadi error sebelumnya bukan hanya di dashboard pemangku tapi juga bug laten di alur Pengangkatan JFT

### 5. Fix duplicate session error saat peserta mulai ujian online
- **UBAH** `app/Http/Controllers/UjikomOnlineController.php`
  - Root cause: `bukaSesi()` membuat baris `ujikom_sesi` massal berstatus `menunggu` (tanpa soal/timer). Pengecekan sesi existing di `mulai()` hanya menangani status `berlangsung`/`selesai`/`timeout`, sehingga status `menunggu` lolos ke `UjikomSesi::create()` lagi dan bentrok dengan unique constraint `(ujikom_jadwal_id, peserta_id)`
  - `mulai()`: jika sesi `menunggu` sudah ada, **update** baris tersebut (isi paket, status→berlangsung, waktu_mulai, batas_waktu, ip_address) alih-alih membuat baris baru

### 6. Validasi satu jadwal hanya boleh punya satu paket ujian aktif
- **UBAH** `app/Http/Controllers/PaketUjianController.php`
  - `aktifkan()`: tolak aktivasi jika jadwal sudah punya paket aktif lain
  - `store()`: tolak simpan jika `simpan_sebagai=aktif` dan jadwal sudah punya paket aktif lain (`update()` tidak disentuh — method itu tidak pernah mengubah kolom `status`)
- **UBAH** `resources/views/paket_ujian/index.blade.php`
  - Alert `alert-warning` jika ada jadwal dengan >1 paket aktif (deteksi via `groupBy`+`having count > 1`)
- **UBAH** `resources/views/ujikom/jadwal/index.blade.php`
  - Kolom baru "Paket Aktif" di tabel admin (badge `badge-success`/`badge-danger` — bukan `bg-success`/`bg-danger`, project ini Bootstrap 4)
- **UBAH** `app/Http/Controllers/UjikomOnlineController.php`
  - Query paket aktif di `mulai()` **dan** `bukaSesi()` (bug identik, disamakan untuk konsistensi) ditambah `->latest()` agar deterministik (ambil yang paling baru diaktifkan) jika ada lebih dari satu

---

## Catatan Teknis

- `doctrine/dbal` tidak terinstall di project ini → migration yang mengubah kolom `enum` **tidak bisa** pakai `->change()`, harus pakai `DB::statement()` raw SQL `ALTER TABLE ... MODIFY COLUMN`
- Sesi `ujikom_sesi` berstatus `menunggu` (dibuat massal via `bukaSesi()`) belum punya soal/timer — jangan pernah redirect langsung ke halaman ujian tanpa mengaktifkannya dulu
- Konvensi UI project ini **Bootstrap 4** — hindari kelas Bootstrap 5 (`form-select`, `bg-success` untuk badge) saat menambah komponen baru

---

## Versi 1.12.0 - Pengangkatan JFT (Rombak Total)
**Tanggal:** 3 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Rombak total modul Pertimbangan Pengangkatan (sebelumnya v1.5.0/v1.5.1) menjadi alur baru berbasis hasil Uji Kompetensi: Admin Unit mengajukan permohonan pengangkatan untuk unit kerjanya → Admin Pusbin memproses (generate ranking kandidat otomatis dari `ujikom_hasil` yang lulus) → setujui → generate surat rekomendasi PDF → konfirmasi TTD → formasi pegawai ter-update otomatis. Konsep "peserta manual per permohonan" pada versi lama dihapus, digantikan generate kandidat otomatis dari data kelulusan ujikom. File & tabel lama disimpan sebagai backup (suffix `_lama`), tidak lagi dipakai di routes.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_03_000001_rombak_pengangkatan_tables.php`
  - Drop tabel lama: `pengangkatan_surat`, `pengangkatan_peserta`, `pengangkatan_permohonan`
  - **BARU** `pengangkatan_permohonan`: kode_permohonan, unit_kerja_id, file_surat_permohonan, tanggal_permohonan, status (draft/diajukan/diproses/disetujui/ditolak/selesai), catatan_pusbin, diajukan_oleh, diproses_oleh, tanggal_disetujui, softDeletes
  - **BARU** `pengangkatan_kandidat` (menggantikan `pengangkatan_peserta`): FK permohonan, pegawai_id, ujikom_hasil_id, jabatan_asal, jenjang_asal, jabatan_tujuan_id, jenjang_tujuan, nilai_ujikom, ranking, formasi_tersedia, status_kandidat (direkomendasikan/antrian/ditolak_pusbin), catatan
  - **BARU** `pengangkatan_surat`: FK permohonan, nomor_surat, file_surat, tanggal_surat, ditandatangani (boolean)

### Model
- **BARU** `app/Models/PengangkatanKandidat.php`
  - Relasi: permohonan(), pegawai() → Sdmmodels, hasilUjikom() → UjikomHasil, jabatanTujuan() → Formasijabatan
  - Accessor: label_status_kandidat, badge_status_kandidat
- **DIROMBAK** `app/Models/PengangkatanPermohonan.php`
  - Relasi baru: unitKerja(), pengaju(), pemroses(), kandidat() → hasMany(PengangkatanKandidat), surat() → hasOne(PengangkatanSurat)
  - `generateKode()`: format `ANGKAT/{bulan-romawi}/{tahun}/{urut}`
  - **BARU** `generateKandidat()`: ambil semua `UjikomHasil` lulus di unit kerja terkait, group per jabatan tujuan, ranking berdasarkan nilai tertinggi, tandai `direkomendasikan` jika ranking ≤ sisa formasi (selebihnya `antrian`)
  - **BARU** `selesaikan()`: update `formasi_jabatan_id` & `tmt_pengangkatan` pegawai untuk semua kandidat berstatus `direkomendasikan`, lalu set status permohonan `selesai`
- **DISEDERHANAKAN** `app/Models/PengangkatanSurat.php` — hanya field nomor_surat, file_surat, tanggal_surat, ditandatangani + relasi permohonan()
- **DIHAPUS** `app/Models/PengangkatanPeserta.php` — digantikan `PengangkatanKandidat`

### Controller
- **DIROMBAK TOTAL** `app/Http/Controllers/PengangkatanController.php` (846 baris → jauh lebih ringkas)
  - `index()`: list permohonan (admin_unit hanya lihat unit sendiri) + 4 stat card (total/diajukan/disetujui/selesai) + filter status/unit_kerja/tahun
  - `create()`, `store()`: buat permohonan baru (upload surat permohonan PDF), opsi langsung ajukan
  - `show()`: detail permohonan + kandidat grouped per jabatan tujuan
  - `edit()`, `update()`, `destroy()`: hanya untuk status `draft`
  - `ajukan()`: draft → diajukan (Admin Unit)
  - **BARU** `proses()`: diajukan → diproses + panggil `generateKandidat()` (Admin Pusbin)
  - **BARU** `setujui()`: diproses → disetujui + buat record `PengangkatanSurat` (Admin Pusbin)
  - `tolak()`: diajukan/diproses → ditolak, wajib catatan_pusbin
  - **BARU** `konfirmasiTtd()`: disetujui → selesai, tandai surat `ditandatangani` + panggil `selesaikan()` (update formasi pegawai)
  - **BARU** `generateSurat()`: PDF surat rekomendasi (DomPDF) berisi kandidat berstatus direkomendasikan
  - **BARU** `updateKandidat()`: AJAX, Admin Pusbin ubah status_kandidat per kandidat (direkomendasikan/antrian/ditolak_pusbin) + catatan
  - Method lama dihapus: `getPegawai`, `getPegawaiList`, `validasiPeserta`, `verifikasi`, `buatDraftSurat`, `konfirmasiParafKatim`, `konfirmasiParafKabid`, `inputNomor`, `simpanNomor`, `selesaikan` (lama), `exportPdf` (lama)
- Disimpan sebagai backup: `app/Http/Controllers/PengangkatanController_lama.php`, `app/Models/PengangkatanPermohonan_lama.php`, `app/Models/PengangkatanPeserta_lama.php`, `app/Models/PengangkatanSurat_lama.php` — tidak direferensikan di routes

### View
- **DIROMBAK** `resources/views/pengangkatan/index.blade.php` — 4 stat card, filter status/unit kerja (admin|super_admin)/tahun, tombol Buat Permohonan (admin_unit|admin|super_admin)
- **DIROMBAK** `resources/views/pengangkatan/create.blade.php` — form permohonan disederhanakan: unit kerja, tanggal permohonan, upload surat permohonan (PDF)
- **DIROMBAK** `resources/views/pengangkatan/show.blade.php` — info permohonan, timeline stepper (Draft → Diajukan → Diproses → Disetujui → TTD → Selesai), daftar kandidat per jabatan tujuan, panel aksi sesuai role & status
- **BARU** `resources/views/pengangkatan/pdf/surat_rekomendasi.blade.php` — PDF surat rekomendasi kandidat direkomendasikan
- **DIHAPUS** `resources/views/pengangkatan/edit.blade.php`, `nomor.blade.php`, `pdf/detail.blade.php`, `pdf/surat_pertimbangan.blade.php`
- Disimpan sebagai backup: `resources/views/pengangkatan_lama/` (create, edit, index, nomor, show, pdf — versi lama utuh)

### Route
- **DIROMBAK** `routes/web.php` — group `pengangkatan.` diganti total:
  - Middleware berbasis role (`role:admin_unit|admin|super_admin` untuk create/store/edit/update/destroy/ajukan; `role:admin|super_admin` untuk proses/setujui/tolak/konfirmasi-ttd/kandidat.update) — sebelumnya pakai `permission:...`
  - Route baru: `proses`, `setujui`, `konfirmasi-ttd` (rename dari `ttd`), `kandidat.update`, `surat` (generate PDF, rename dari `export`)
  - Route dihapus: `get-pegawai`, `pegawai-list`, `validasi-peserta`, `verifikasi`, `draft-surat`, `paraf-katim`, `paraf-kabid`, `nomor`, `simpan-nomor`, `selesaikan` (lama)

### Sidebar
- **UBAH** `resources/views/layouts/users/master.blade.php`
  - Menu "Pengembangan Karir JFT" kini juga tampil untuk `admin_unit` (sebelumnya hanya admin|super_admin)
  - Label menu "Pertimbangan Pengangkatan" → "Pengangkatan JFT"
  - Tambah highlight `active`/`menu-open` otomatis berdasar `request()->routeIs('pengangkatan.*')`

---

## Catatan Teknis

- `generateKandidat()` selalu hapus kandidat lama sebelum generate ulang (`$this->kandidat()->delete()`) — aman dipanggil ulang saat re-proses
- Status `direkomendasikan` ditentukan murni oleh ranking vs sisa formasi saat `generateKandidat()` dijalankan; Admin Pusbin tetap bisa override manual via `updateKandidat()`
- Tabel `pengangkatan_kandidat` **tidak** soft delete — kandidat dihapus permanen saat regenerate
- Field `unit_kerja_id` bertipe string, relasi ke `Rumahsakit` via kolom `no_rs` (bukan `id`), konsisten dengan modul lain (Formasi, Ujikom)

---

## Versi 1.11.0 - Dashboard per Role
**Tanggal:** 1 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi Dashboard per Role: tampilan dashboard kini menyesuaikan diri berdasarkan role user yang login. Admin Unit mendapat dashboard unit-spesifik. Pemangku mendapat dashboard personal. Super Admin & Admin mendapat tambahan card "Perlu Tindakan" berisi notifikasi permohonan dan sesi aktif. Viewer tetap melihat dashboard nasional seperti sebelumnya.

---

## Perubahan

### Controller
- **DIUBAH** `app/Http/Controllers/PetaDashboardController.php`
  - Tambah import: `UjikomJadwal`, `UjikomPendaftaran`, `UjikomHasil`, `UjikomSesi`, `Sdmmodels`
  - `index()`: deteksi role di awal — early return untuk `admin_unit` dan `pemangku`; tambah `$perluTindakan` untuk `super_admin|admin`
  - **BARU** `dashboardAdminUnit($user)`: total pegawai unit, rekap formasi (kuota/terisi/sisa), permohonan counts per status, jadwal ujikan aktif
  - **BARU** `dashboardPemangku($user)`: profil SDM, jadwal terdekat (max 3), riwayat hasil ujian (max 10), permohonan aktif (max 5)

### View
- **DIUBAH** `resources/views/users/dashboard.blade.php`
  - Tambah section `@role('pemangku')`: profil pemangku, jadwal terdekat, riwayat hasil ujian, permohonan aktif
  - Tambah section `@role('admin_unit')`: info unit kerja, 4 stat cards, tabel rekap formasi, jadwal ujikan aktif
  - Tambah block `@hasanyrole('super_admin|admin')` → card "Perlu Tindakan" dengan 5 metric: permohonan pending pusbin/admin_unit, jadwal aktif, sesi berlangsung, hasil belum dinilai
  - Existing national dashboard dibungkus `@hasanyrole('super_admin|admin|viewer')`
  - Link metric "sesi berlangsung" mengarah ke `route('ujikom-online.index')` — bukan `ujikom-online.monitoring` (route tersebut tidak terdaftar; monitoring per-jadwal diakses dari dalam halaman index)

---

## Versi 1.10.0 - Modul Hasil Uji Kompetensi
**Tanggal:** 2 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi modul Hasil Uji Kompetensi sebagai sub-menu 1.4 di Kompetensi JFT. Menggantikan placeholder "Coming Soon". Tabel `ujikom_hasil` menjadi sumber kebenaran tunggal untuk hasil ujian, baik dari ujian online (auto-sync) maupun offline (input manual admin). Peserta pemangku dapat melihat riwayat ujikom milik sendiri.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_01_000004_create_ujikom_hasil_table.php`
  - Tabel `ujikom_hasil`: FK jadwal, sesi (nullable), peserta; enum jenis_ujian (online/offline); nilai, status_kelulusan, passing_grade, catatan_admin, dinilai_oleh, tanggal_ujian
  - unique(ujikom_jadwal_id, peserta_id) — satu peserta satu hasil per jadwal

### Model
- **BARU** `app/Models/UjikomHasil.php`
  - Relasi: jadwal(), sesi(), peserta(), penilai()
  - Accessor: label_status ('Lulus'/'Tidak Lulus'/'Belum Dinilai'), badge_status ('success'/'danger'/'secondary'), label_jenis ('Online'/'Offline')

- **UBAH** `app/Models/UjikomSesi.php`
  - Tambah method `syncKeHasil()`: sync nilai ujian online ke `ujikom_hasil` via `updateOrCreate`
  - Tambah panggilan `$this->syncKeHasil()` di akhir `hitungNilai()` — otomatis mencakup semua jalur finalisasi (submit peserta, force submit admin, tutup sesi admin)

- **UBAH** `app/Models/UjikomJadwal.php`
  - Tambah relasi `hasilUjikom()` → hasMany(UjikomHasil)
  - Tambah relasi `pendaftaran()` → hasMany(UjikomPendaftaran)

### Export
- **BARU** `app/Exports/UjikomHasilExcelExport.php`
  - Implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
  - Header biru tua (#1F3864), 12 kolom, semua peserta terverifikasi (termasuk yang belum punya nilai)

### Controller
- **BARU** `app/Http/Controllers/UjikomHasilController.php` — 8 method:
  - `index()`: rekap per jadwal + statistik global + filter tahun/jenis
  - `show($jadwalId)`: detail peserta + distribusi nilai 5 rentang + statistik kelulusan
  - `inputOffline($jadwalId)`: form input nilai peserta belum dinilai
  - `simpanOffline($jadwalId)`: simpan hasil offline dengan passing grade dari paket aktif
  - `updateCatatan($hasilId)`: AJAX update catatan admin
  - `exportPdf($jadwalId)`: DomPDF landscape — kop surat, statistik, tabel, TTD kosong
  - `exportExcel($jadwalId)`: Maatwebsite Excel download
  - `riwayatPeserta()`: riwayat ujikom pemangku berdasar `user->sdm_id`

### View
- **BARU** `resources/views/ujikom/hasil/index.blade.php`
  - 5 statistik card global (total jadwal, peserta, lulus, tidak lulus, belum dinilai)
  - Tabel jadwal: badge Campuran/Online/Offline, kolom lulus/tidak/belum, aksi Detail+PDF+Excel
  - Filter tahun + jenis ujian

- **BARU** `resources/views/ujikom/hasil/show.blade.php`
  - 4 info-box statistik + bar chart distribusi nilai (CSS-only, 5 rentang: 0-60, 61-70, 71-80, 81-90, 91-100)
  - Tabel peserta: inline edit catatan admin via AJAX (tampil/form toggle)
  - Tombol Input Offline, Export PDF, Export Excel

- **BARU** `resources/views/ujikom/hasil/input_offline.blade.php`
  - Tabel peserta belum dinilai, auto-set status lulus/tidak dari nilai vs passing grade (jQuery)
  - Tanggal ujian default = hari ini

- **BARU** `resources/views/ujikom/hasil/riwayat.blade.php`
  - Tabel riwayat ujikom peserta: jadwal, tanggal, jenis, nilai, passing grade, status kelulusan, catatan

- **BARU** `resources/views/ujikom/hasil/pdf/rekap.blade.php`
  - Layout landscape, kop surat (`public/images/kop_surat.png`), statistik summary, tabel peserta, TTD kosong

### Route
- **UBAH** `routes/web.php`
  - Hapus placeholder `ujikom.hasil.index` (coming soon)
  - Tambah group `Route::prefix('ujikom/hasil')->name('ujikom.hasil.')` dengan 8 route
  - Static routes (`riwayat/saya`, `catatan/{hasilId}`) diprioritaskan sebelum wildcard `/{jadwalId}`
  - Ditempatkan sebelum group `ujikom/{id}` untuk mencegah konflik wildcard

### Sidebar
- **UBAH** `resources/views/layouts/users/master.blade.php`
  - "Hasil Uji Kompetensi (Segera)" → link aktif dengan kondisi role:
    - Pemangku → `ujikom.hasil.riwayat`
    - Admin/Super Admin → `ujikom.hasil.index`
  - Wrapped dalam `@hasanyrole('super_admin|admin|admin_unit|pemangku')`

---

## Catatan Teknis

- `syncKeHasil()` menggunakan `updateOrCreate` dengan key `[ujikom_jadwal_id, peserta_id]` sehingga aman dipanggil berkali-kali (idempotent)
- Nilai offline tidak menimpa nilai online jika jenis_ujian berbeda (karena unique constraint per jadwal+peserta, hanya 1 hasil per peserta per jadwal)
- `riwayatPeserta()` gunakan `user->sdm_id` → cari peserta_id → load hasil, konsisten dengan pola auth di modul ujikom online

---

## Versi 1.9.0 - Modul Bank Soal, Paket Ujian & Ujikom Online
**Tanggal:** 1 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi 3 modul besar sekaligus: Bank Soal (CRUD + import Excel), Paket Ujian (builder soal manual/acak), dan Ujikom Online (CAT-style exam engine dengan monitoring admin real-time).

---

## Modul A: Bank Soal (v1.9.0-a)

### Database
- **BARU** `database/migrations/2026_07_01_000001_create_bank_soal_tables.php`
  - `soal_kategori`: id, nama, jenjang (enum), matra (enum), deskripsi, status (aktif/nonaktif)
  - `bank_soal`: semua field soal (pertanyaan, jenis, tingkat_kesulitan, taksonomi_bloom, kategori FK, status, softDeletes)
  - `bank_soal_pilihan`: kode_pilihan A-D, teks, is_benar, cascade delete
  - `bank_soal_import_log`: log per batch import

### Model
- **BARU** `app/Models/SoalKategori.php` — scope aktif(), accessor label_jenjang, label_matra
- **BARU** `app/Models/BankSoal.php` — scope aktif/umum/spesifik, accessor label_tingkat/badge_tingkat/label_taksonomi, relasi pilihan/jawabanBenar/kategori
- **BARU** `app/Models/BankSoalPilihan.php`
- **BARU** `app/Models/BankSoalImportLog.php`

### Controller
- **BARU** `app/Http/Controllers/BankSoalController.php` — 10 method (index, create, store, show, edit, update, destroy, aktifkan, import GET/POST, downloadTemplate)
- **BARU** `app/Http/Controllers/SoalKategoriController.php` — 4 method CRUD

### Import/Export
- **BARU** `app/Imports/BankSoalImport.php` — ToCollection, WithHeadingRow, SkipsOnError, auto-log
- **BARU** `app/Exports/BankSoalTemplateExport.php` — 3 sheet: Petunjuk, Daftar Kategori, Data Soal

### Seeder
- **BARU** `database/seeders/SoalKategoriSeeder.php` — 10 kategori soal default

### View
- **BARU** `resources/views/bank_soal/index.blade.php` — tabel + filter status/kategori/tingkat
- **BARU** `resources/views/bank_soal/create.blade.php` — form dinamis pilihan A-D, auto-isi kode benar
- **BARU** `resources/views/bank_soal/edit.blade.php`
- **BARU** `resources/views/bank_soal/show.blade.php` — preview soal + pilihan + badge status
- **BARU** `resources/views/bank_soal/import.blade.php` — upload Excel + riwayat log + modal detail error
- **BARU** `resources/views/soal_kategori/index.blade.php`

### Route
- 10 route `/bank-soal/...` (CRUD + import + template)
- 4 route `/soal-kategori/...` (CRUD)
- Static routes `get-by-kategori`, `import`, `template` ditempatkan **sebelum** `/{id}` untuk cegah konflik

---

## Modul B: Paket Ujian (v1.9.0-b)

### Database
- **BARU** `database/migrations/2026_07_01_000002_create_paket_ujian_tables.php`
  - `paket_ujian`: FK jadwal (nullable), mode_pemilihan (acak_otomatis/manual), status, softDeletes
  - `paket_ujian_soal`: pivot bank_soal ↔ paket, urutan
  - `paket_ujian_kategori_acak`: konfigurasi per kategori untuk mode acak

### Model
- **BARU** `app/Models/PaketUjian.php`
  - `generateSoalUntukPeserta($id)`: generate set soal (acak atau manual) + shuffle urutan/pilihan
  - `cekKetersediaanSoal()`: cek apakah bank soal mencukupi konfigurasi acak
  - Accessor: label_status, badge_status, label_mode
- **BARU** `app/Models/PaketUjianSoal.php`
- **BARU** `app/Models/PaketUjianKategoriAcak.php`

### Controller
- **BARU** `app/Http/Controllers/PaketUjianController.php` — 11 method (CRUD + aktifkan + nonaktifkan + previewSoal + getSoalByKategori)

### View
- **BARU** `resources/views/paket_ujian/index.blade.php` — statistik (total/aktif/draft/nonaktif) + filter + tabel
- **BARU** `resources/views/paket_ujian/create.blade.php` — builder dua mode: acak (konfigurasi per kategori) + manual (two-panel AJAX soal pilih)
- **BARU** `resources/views/paket_ujian/edit.blade.php`
- **BARU** `resources/views/paket_ujian/show.blade.php` — info paket, ketersediaan soal, daftar soal (manual) / konfigurasi acak + preview AJAX

### Route
- 12 route `/paket-ujian/...`
- `get-soal-kategori` ditempatkan **sebelum** `/{id}` untuk cegah konflik

---

## Modul C: Ujikom Online (v1.9.0-c)

### Database
- **BARU** `database/migrations/2026_07_01_000003_create_ujikom_sesi_tables.php`
  - `ujikom_sesi`: FK jadwal + paket + peserta, status_sesi, waktu_mulai/selesai/batas, nilai_akhir, status_lulus, unique(jadwal, peserta)
  - `ujikom_sesi_soal`: soal per peserta + jawaban (pilihan_dipilih, is_benar, waktu_dijawab), unique(sesi, soal)
  - `ujikom_sesi_log`: audit trail setiap aksi (mulai/jawab/navigasi/submit/timeout/peringatan)

### Model
- **BARU** `app/Models/UjikomSesi.php`
  - `hitungNilai()`: hitung nilai akhir, benar/salah/kosong, status lulus vs passing grade
  - `getSisaWaktuAttribute()`: sisa waktu dalam detik (real-time)
  - `getProgressAttribute()`: progress soal dijawab / total
- **BARU** `app/Models/UjikomSesiSoal.php` — eager load `bankSoal.pilihan`
- **BARU** `app/Models/UjikomSesiLog.php` — cast detail → array

### Controller
- **BARU** `app/Http/Controllers/UjikomOnlineController.php` — 11 method:
  - Peserta: `index`, `mulai`, `ujian`, `jawab` (AJAX), `navigasi` (AJAX), `submit`, `hasil`
  - Admin: `bukaSesi`, `tutupSesi`, `monitoring`, `forceSubmit`

### View
- **BARU** `resources/views/ujikom/online/index.blade.php` — dual view: admin (tabel manajemen sesi + Buka/Tutup/Monitoring) + peserta (card jadwal + status sesi)
- **BARU** `resources/views/ujikom/online/ujian.blade.php` — CAT-style: timer countdown real-time, panel navigasi soal (hijau=dijawab/merah=belum/biru=aktif), AJAX save jawaban tanpa reload, auto-submit saat timeout, prevent browser back
- **BARU** `resources/views/ujikom/online/hasil.blade.php` — card nilai besar + lulus/tidak lulus + statistik benar/salah/kosong + durasi
- **BARU** `resources/views/ujikom/online/monitoring.blade.php` — tabel progress semua peserta + force submit, auto-refresh 30 detik

### Route
- **UBAH** `routes/web.php`: Ganti placeholder `ujikom.online.index` → 11 route baru
  - Peserta: `/ujikom-online/...` (7 route)
  - Admin: `/ujikom-online/admin/...` (4 route)

### Sidebar
- **UBAH** `resources/views/layouts/users/master.blade.php`: Sidebar "Uji Kompetensi (Segera)" → link aktif ke `ujikom-online.index`

---

## Catatan Teknis

- **Keamanan jawaban:** Method `jawab()` TIDAK mengembalikan info benar/salah ke frontend
- **Auto-submit timeout:** Timer JS → auto-submit form saat `sisaDetik <= 0`; controller juga cek `batas_waktu` saat `jawab()` dipanggil
- **Akses peserta:** `authorizeAksesSesi()` cek `sdm_id` user vs `peserta.pegawai_id`
- **Paket Ujian → Jadwal:** Relasi via `ujikom_jadwal_id` di `paket_ujian`; satu jadwal bisa punya banyak paket, sistem ambil yang `status = aktif`

---

## Versi 1.8.3 - Fix Tampilan Halaman Detail Pendaftaran & Jadwal Ujikom
**Tanggal:** 29 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Serangkaian perbaikan tampilan di dua halaman: detail pendaftaran ujikom (`show.blade.php`) dan detail jadwal ujikom (`ujikom/jadwal/show.blade.php`). Mencakup fix label status badge, kolom jabatan tujuan yang duplikat, timeline stepper 5 langkah, dan kolom peserta terdaftar di halaman jadwal.

---

## Perubahan

### Model
- **UBAH** `app/Models/UjikomPendaftaran.php`
  - `getLabelStatusAttribute()`: ganti logika label "Diajukan" — pakai `jenis_pendaftaran === 'mandiri'` → "Diajukan Pemangku", selain itu → "Diajukan Admin Unit". Label `diajukan_pusbin` diubah jadi "Diteruskan ke Pusbin"
  - `getBadgeStatusAttribute()`: warna `diverifikasi_admin_unit` diubah ke `info`

### Controller
- **UBAH** `app/Http/Controllers/UjikomJadwalController.php`
  - `show()`: eager load diperbarui dari `peserta.pegawai` → `peserta.pegawai.formasi.jenjang` + `peserta.jabatanTujuan` agar nama, jabatan, dan jenjang pegawai tampil dengan benar

### View
- **UBAH** `resources/views/ujikom/pendaftaran/show.blade.php`
  - **Timeline stepper:** Diperbarui jadi 5 langkah: Draft → Diajukan → Verifikasi Admin Unit → Verifikasi Pusbin → Selesai. Mapping status ke step aktif sudah benar. Status ditolak menampilkan ikon ✗ merah di step yang sesuai.
  - **Kolom Jabatan Tujuan:** Fix duplikat — sebelumnya `jabatan_tujuan_nama` menggabungkan nama jabatan + jenjang. Sekarang pakai `jabatanTujuan->nama_formasi` (hanya nama jabatan) dan `jenjang_tujuan` (hanya jenjang) secara terpisah.

- **UBAH** `resources/views/ujikom/jadwal/show.blade.php`
  - **Kolom "Nama Pegawai":** Fix kolom `nama` → `nama_lengkap` (sesuai kolom di tabel `sumber_daya_manusia`)
  - **Kolom "Jabatan / Jenjang":** Dipisah — sekarang hanya menampilkan jenjang saat ini (`formasi->jenjang->nama_jenjang`)
  - **Kolom baru "Jabatan Tujuan":** Tambah kolom yang menampilkan `jabatanTujuan->nama_formasi / jenjang_tujuan`

---

## Versi 1.8.2 - Fix Alur & Role Panel Aksi Pendaftaran Ujikom
**Tanggal:** 25 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Perbaikan alur status dan pemisahan panel aksi berdasarkan role di halaman detail pendaftaran ujikom. Sebelumnya tombol aksi untuk Admin Unit dan Admin Pusbin tidak terpisah dengan benar — admin Pusbin bisa lihat panel Admin Unit, dan terdapat status intermediate `diverifikasi_admin_unit` yang tidak perlu.

---

## Perubahan

### Controller
- **UBAH** `app/Http/Controllers/UjikomPendaftaranController.php`
  - `verifikasiAdminUnit()`: status langsung ke `diajukan_pusbin` (sebelumnya ke `diverifikasi_admin_unit`)
  - Success message diperbarui: "diteruskan ke Pusbin"

### View
- **UBAH** `resources/views/ujikom/pendaftaran/show.blade.php`
  - **Timeline stepper:** Dikurangi dari 6 steps → 4 steps (hapus `diverifikasi_admin_unit` dan `diverifikasi_pusbin` karena tidak muncul sebagai status aktual). Mapping status ke display step ditambahkan. Setiap step aktif sekarang punya subtitle "Menunggu...".
  - **Panel `diajukan_admin_unit`:** `@hasanyrole` diubah dari `super_admin|admin|admin_unit` → `super_admin|admin_unit` (admin Pusbin tidak lagi dapat tombol di tahap ini)
  - **Panel `diverifikasi_admin_unit`:** Dihapus sepenuhnya (tahap ini tidak lagi digunakan dalam alur)
  - **Tombol verifikasi admin unit:** Label diubah dari "Verifikasi & Teruskan ke Pusbin" → "Verifikasi & Kirim ke Pusbin"

---

## Alur Status Final

```
draft
  → [Operator/Pemangku] diajukan_admin_unit
      → [Admin Unit/Super Admin] diajukan_pusbin   ← langsung, tanpa intermediate
          → [Admin Pusbin/Super Admin] selesai
      → [Admin Unit/Super Admin] ditolak_admin_unit
  → [Admin Pusbin/Super Admin] ditolak_pusbin
```

---

## Versi 1.8.1 - Kolom Jabatan Tujuan di Pendaftaran Ujikom
**Tanggal:** 23 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Perbaikan tabel peserta pendaftaran ujikom agar menampilkan jabatan dan jenjang tujuan. Sebelumnya kolom tersebut tidak tersimpan ke database dan tidak ditampilkan di halaman detail.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_06_23_000002_add_jabatan_tujuan_to_ujikom_pendaftaran_peserta.php`
  - Tambah kolom `jabatan_tujuan_id` (unsignedBigInteger, FK → `formasi_jabatan`, nullable)
  - Tambah kolom `jenjang_tujuan` (string, nullable) — snapshot nama jenjang tujuan
  - Tambah kolom `jabatan_tujuan_nama` (string, nullable) — snapshot "NamaFormasi Jenjang" sebagai backup

### Model
- **UBAH** `app/Models/UjikomPendaftaranPeserta.php`
  - Tambah 3 kolom baru ke `$fillable`
  - Tambah relasi `jabatanTujuan()` → `belongsTo(Formasijabatan::class, 'jabatan_tujuan_id')`

### Controller
- **UBAH** `app/Http/Controllers/UjikomPendaftaranController.php`
  - `store()`: ambil `Formasijabatan` dari `formasi_tujuan_id` (input form), simpan ke 3 kolom baru
  - `update()`: sama seperti `store()`

### View
- **UBAH** `resources/views/ujikom/pendaftaran/show.blade.php`
  - Header tabel peserta: 6 → 8 kolom (tambah "Jabatan Tujuan" & "Jenjang Tujuan")
  - Rename: "Jabatan" → "Jabatan Saat Ini", "Jenjang" → "Jenjang Saat Ini"
  - Body tabel: tampilkan `jabatan_tujuan_nama` dan `jenjang_tujuan` (fallback ke relasi)
  - Empty row colspan: 6 → 8

---

## Versi 1.8.0 - Role Pemangku & Login NIP
**Tanggal:** 22 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Penambahan role `pemangku` khusus untuk JFT yang bisa login menggunakan NIP. Kolom `username` dan `sdm_id` ditambah ke tabel `users`. Form manajemen user diperbarui untuk mendukung pembuatan akun pemangku dengan pemilihan data SDM.

---

## 1. Fitur Utama

### Login via NIP
- Halaman login sekarang mendukung input Email **atau** NIP di satu field
- Deteksi otomatis: jika input lolos validasi email → auth by email, jika tidak → auth by username (NIP)

### Role Pemangku
- Role baru `pemangku` dengan permission terbatas: `view dashboard`, `view ujikom jadwal`, `view ujikom permohonan`, `create ujikom permohonan`
- Akun pemangku dibuat dengan memilih data pegawai SDM; username = NIP, password default = NIP

### Manajemen User — Form
- Ketika role `pemangku` dipilih, form berubah: muncul dropdown unit kerja + dropdown pegawai SDM (AJAX)
- Field nama & email standar disembunyikan dan diganti dengan data dari SDM
- Info box menampilkan nama & NIP pegawai terpilih beserta informasi bahwa NIP = username/password

### Manajemen User — Index
- Kolom baru "Username / NIP" di tabel
- Badge warna `badge-info` untuk role pemangku

---

## 2. Perubahan Database

### Migration Baru
- `database/migrations/2026_06_23_000001_add_username_sdm_id_to_users_table.php`
  - `username` string nullable unique (NIP sebagai username login)
  - `sdm_id` unsignedBigInteger nullable FK → `sumber_daya_manusia(id)` onDelete set null

---

## 3. File yang Dibuat / Diubah

- **UBAH** `app/Models/User.php` — tambah `username`, `sdm_id`, `status` ke `$fillable`; tambah relasi `sdm()`
- **UBAH** `app/Http/Controllers/CentralController.php` — `LoginAksi()` deteksi email vs NIP, input field `login`
- **UBAH** `resources/views/main/login.blade.php` — label & input field `login`, placeholder "Email atau NIP"
- **UBAH** `app/Http/Controllers/UserController.php` — `create/edit` pass `$unitKerjas`; `store/update` logika bifurkasi pemangku vs non-pemangku
- **UBAH** `database/seeders/PermissionSeeder.php` — tambah permission `view/create ujikom jadwal/permohonan`, buat role `pemangku` + syncPermissions
- **UBAH** `resources/views/users/manajemen-user/form.blade.php` — section pemangku (unit kerja + SDM dropdown AJAX, info box NIP), show/hide berdasarkan role
- **UBAH** `resources/views/users/manajemen-user/index.blade.php` — tambah kolom "Username / NIP", badge pemangku
- **UBAH** `resources/views/ujikom/pendaftaran/show.blade.php` — fix `nama` → `nama_lengkap`, `nama_jabatan` → `nama_formasi`, `jenjang` → `formasi.jenjang`
- **UBAH** `app/Http/Controllers/UjikomPendaftaranController.php` — `show()` eager load `peserta.pegawai.formasi.jenjang`

---

## Versi 1.7.0 - Modul Pendaftaran Uji Kompetensi
**Tanggal:** 22 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Modul lengkap untuk pendaftaran/permohonan mengikuti Uji Kompetensi JFT. Operator/Admin dapat mendaftarkan peserta (single atau batch per unit kerja) ke jadwal ujikom yang sudah dipublikasikan. Dilengkapi workflow 8 status, pengecekan kuota formasi real-time, upload berkas persyaratan per peserta, dan timeline progress.

---

## 1. Fitur Utama

### Pendaftaran (Operator)
- Pilih jadwal ujikom, unit kerja, jenis pendaftaran (mandiri/batch)
- AJAX card jadwal info (tanggal, tempat, kuota, sisa)
- Select2 AJAX untuk pilih pegawai (difilter per unit kerja)
- Pengecekan formasi otomatis per pegawai (sisa kuota & status)
- Upload berkas persyaratan per peserta (grid: peserta × persyaratan)
- Simpan sebagai Draft atau langsung Ajukan

### Workflow 8 Status
```
draft → diajukan_admin_unit → diverifikasi_admin_unit → diajukan_pusbin → diverifikasi_pusbin → selesai
                           ↘ ditolak_admin_unit        ↘ ditolak_pusbin
```

### Verifikasi (Admin/Super Admin)
- Verifikasi Admin Unit: approve/tolak dari admin unit
- Ajukan ke Pusbin: diteruskan ke Pusbin JFT
- Verifikasi Pusbin: approve/tolak dari Pusbin
- Verifikasi berkas: set status per berkas (diterima/ditolak) + catatan
- Modal form untuk input catatan penolakan

### Halaman Detail (show)
- Timeline stepper 6-tahap (progress visual)
- Alert merah jika ditolak + catatan penolakan
- Tabel peserta + badge status formasi
- Berkas per peserta dikelompokkan per persyaratan
- Tombol aksi kontekstual per status & role

---

## 2. Struktur Database

### Tabel Baru

#### `ujikom_pendaftaran`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| kode_pendaftaran | string unique | Format: DAFTAR-UJIKOM/VI/2026/0001 |
| ujikom_jadwal_id | FK → ujikom_jadwal | restrict |
| unit_kerja_id | unsignedBigInteger | FK → rumahsakits(no_rs) |
| jenis_pendaftaran | enum | mandiri / batch |
| status | enum | 8 nilai (lihat workflow) |
| catatan_admin_unit | text nullable | Catatan tolak admin unit |
| catatan_pusbin | text nullable | Catatan tolak pusbin |
| dibuat_oleh | FK → users | User pembuat |
| timestamps | - | created_at, updated_at |

#### `ujikom_pendaftaran_peserta`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| ujikom_pendaftaran_id | FK cascade | |
| pegawai_id | FK → sumber_daya_manusia | |
| sisa_formasi | integer nullable | Snapshot saat daftar |
| status_formasi | enum | tersedia / tidak_tersedia |

#### `ujikom_pendaftaran_berkas`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| ujikom_pendaftaran_id | FK cascade | |
| pegawai_id | FK → sumber_daya_manusia | |
| ujikom_persyaratan_id | FK → ujikom_persyaratan | |
| nama_berkas | string | Nama file asli |
| file_path | string | Path di storage/public |
| status_verifikasi | enum | belum / diterima / ditolak |
| catatan | text nullable | Catatan verifikator |

---

## 3. File yang Dibuat / Diubah

### Migration
- **BARU** `database/migrations/2026_06_22_000002_create_ujikom_pendaftaran_tables.php`
  - Catatan penting: `unit_kerja_id` menggunakan `unsignedBigInteger` (bukan `foreignId`) karena `rumahsakits.no_rs` bertipe `bigint unsigned`

### Models
- **BARU** `app/Models/UjikomPendaftaran.php`
  - Relations: jadwal, unitKerja (via no_rs), pembuat, peserta, berkas
  - Accessors: `getLabelStatusAttribute()`, `getBadgeStatusAttribute()`
  - Static: `generateKode()` — format `DAFTAR-UJIKOM/[ROMAWI]/[TAHUN]/[0000]`
  - Helpers: `bisaDiedit()`, `bisaDihapus()`, `bisaDiajukan()`

- **BARU** `app/Models/UjikomPendaftaranPeserta.php`
- **BARU** `app/Models/UjikomPendaftaranBerkas.php`

### Controller
- **BARU** `app/Http/Controllers/UjikomPendaftaranController.php` (17 method)
  - AJAX: `getJadwalInfo()`, `getPegawaiList()`, `cekFormasi()`
  - CRUD: index, create, store, show, edit, update, destroy
  - Workflow: ajukan, verifikasiAdminUnit, tolakAdminUnit, ajukanPusbin, verifikasiPusbin, tolakPusbin, verifikasiBerkas

### Views
- **BARU** `resources/views/ujikom/pendaftaran/index.blade.php` — tabel + filter status/jadwal
- **BARU** `resources/views/ujikom/pendaftaran/create.blade.php` — form 3 section + AJAX
- **BARU** `resources/views/ujikom/pendaftaran/edit.blade.php` — sama seperti create, pre-filled
- **BARU** `resources/views/ujikom/pendaftaran/show.blade.php` — detail + timeline + berkas + aksi

### Routes
- **UBAH** `routes/web.php`
  - Tambah `use App\Http\Controllers\UjikomPendaftaranController`
  - Tambah group `ujikom/pendaftaran` (prefix, name `ujikom.permohonan.`) — ditempatkan **SEBELUM** group `ujikom/{id}`
  - Hapus placeholder route `ujikom.permohonan.index` (coming soon)

### Perubahan lainnya
- **UBAH** `app/Http/Controllers/UjikomJadwalController.php`
  - `show()` sekarang query `UjikomPendaftaran` yang sudah diverifikasi pusbin/selesai
  - Pass `$pendaftaranList` dan `$totalPeserta` ke view
- **UBAH** `resources/views/ujikom/jadwal/show.blade.php`
  - Section "Peserta Terdaftar" sekarang menampilkan data nyata dari pendaftaran
  - Badge jumlah peserta dinamis
  - Tombol "Daftar Ujikom" untuk operator/admin
  - List peserta dikelompokkan per pendaftaran/unit kerja

```
Daftar route ujikom.permohonan.*:
GET    /ujikom/pendaftaran                          index
GET    /ujikom/pendaftaran/create                   create
POST   /ujikom/pendaftaran                          store
GET    /ujikom/pendaftaran/jadwal-info/{jadwalId}   jadwal-info    [AJAX]
GET    /ujikom/pendaftaran/pegawai-list             pegawai-list   [AJAX]
GET    /ujikom/pendaftaran/cek-formasi/{pegawaiId}  cek-formasi    [AJAX]
GET    /ujikom/pendaftaran/{id}                     show
GET    /ujikom/pendaftaran/{id}/edit                edit
PUT    /ujikom/pendaftaran/{id}                     update
DELETE /ujikom/pendaftaran/{id}                     destroy
POST   /ujikom/pendaftaran/{id}/ajukan              ajukan
POST   /ujikom/pendaftaran/{id}/verifikasi-admin    verifikasi.admin
POST   /ujikom/pendaftaran/{id}/tolak-admin         tolak.admin
POST   /ujikom/pendaftaran/{id}/ajukan-pusbin       ajukan.pusbin
POST   /ujikom/pendaftaran/{id}/verifikasi-pusbin   verifikasi.pusbin
POST   /ujikom/pendaftaran/{id}/tolak-pusbin        tolak.pusbin
POST   /ujikom/pendaftaran/{id}/verifikasi-berkas   verifikasi.berkas
```

---

## 4. Catatan Teknis

### Foreign Key unit_kerja_id
`rumahsakits.no_rs` bertipe `bigint unsigned`, bukan `id` auto-increment. Harus menggunakan `unsignedBigInteger('unit_kerja_id')` + manual `$table->foreign('unit_kerja_id')->references('no_rs')->on('rumahsakits')`. Tidak bisa menggunakan `foreignId()->constrained()`.

### AJAX Form Create/Edit
Form menggunakan JavaScript state (`pesertaList`, `persyaratanList`) untuk membangun tabel peserta dan grid upload berkas secara dinamis tanpa reload halaman.

### Berkas Upload
- Path: `storage/app/public/ujikom/berkas/`
- Diakses via `asset('storage/ujikom/berkas/...')`
- Nama input: `berkas[{pesertaIdx}][{persyaratanId}]`

---

## 5. Testing Checklist

- [x] Migration berjalan tanpa error
- [x] Semua 17 route ujikom.permohonan.* terdaftar
- [x] Route `ujikom/pendaftaran` tidak konflik dengan `ujikom/{id}`
- [ ] Operator bisa buat pendaftaran baru
- [ ] AJAX jadwal info berfungsi
- [ ] AJAX select2 pegawai berfungsi (filter per unit kerja)
- [ ] AJAX cek formasi berfungsi
- [ ] Grid berkas muncul otomatis setelah pilih jadwal + tambah peserta
- [ ] Simpan draft berfungsi
- [ ] Simpan & ajukan berfungsi
- [ ] Workflow verifikasi admin unit berfungsi
- [ ] Workflow verifikasi pusbin berfungsi
- [ ] Halaman detail jadwal menampilkan peserta yang sudah diverifikasi

---

## Versi 1.6.0 - Modul Pengumuman Jadwal Uji Kompetensi
**Tanggal:** 22 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Modul baru untuk mengelola dan mengumumkan jadwal Uji Kompetensi JFT kepada peserta. Admin/Super Admin dapat membuat, mengedit, dan mempublikasikan jadwal. Operator dan Viewer melihat jadwal yang sudah dipublikasikan dalam tampilan card. Dilengkapi manajemen persyaratan peserta dengan upload file contoh.

---

## 1. Fitur Utama

### Manajemen Jadwal (Admin/Super Admin)
- Buat jadwal dengan judul, deskripsi, tanggal mulai/selesai, tempat, kuota
- Status jadwal: **Draft → Dipublikasikan → Selesai**
- Tombol "Publikasikan" dan "Tandai Selesai" langsung dari halaman detail
- Edit jadwal (hanya status draft)
- Hapus jadwal (hanya status draft)
- Tabel daftar semua jadwal + filter berdasarkan status

### Tampilan Publik (Operator/Viewer)
- Hanya menampilkan jadwal berstatus `published`
- Tampilan card grid (3 kolom): judul, tanggal, tempat, kuota, tombol "Lihat Detail"
- Pesan kosong jika tidak ada jadwal aktif

### Persyaratan Peserta
- Input dinamis (tambah/hapus baris) saat create/edit jadwal
- Kolom: Nama Syarat, Keterangan, Urutan, Upload File Contoh (PDF/DOC/XLS/IMG, max 5MB)
- File disimpan di `storage/public/ujikom/persyaratan/`
- Link download file contoh di halaman detail

### Placeholder "Coming Soon"
- 6 route placeholder dengan halaman `coming_soon.blade.php`
- Digunakan oleh menu sidebar yang belum diimplementasikan

---

## 2. Struktur Database

### Tabel Baru

#### `ujikom_jadwal`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| judul | string | Judul jadwal |
| deskripsi | text nullable | Keterangan tambahan |
| tanggal_mulai | date | Tanggal mulai ujian |
| tanggal_selesai | date | Tanggal selesai ujian |
| tempat | string | Lokasi pelaksanaan |
| kuota | integer | Jumlah peserta yang diterima |
| status | enum | draft / published / selesai |
| dibuat_oleh | FK → users | User pembuat |
| timestamps | - | created_at, updated_at |

#### `ujikom_persyaratan`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| ujikom_jadwal_id | FK → ujikom_jadwal | Cascade delete |
| nama_syarat | string | Nama persyaratan |
| keterangan | text nullable | Keterangan detail |
| file_contoh | string nullable | Path file di storage |
| urutan | integer | Urutan tampil (default: 1) |
| timestamps | - | created_at, updated_at |

---

## 3. File yang Dibuat

### Migration
- **BARU** `database/migrations/2026_06_22_000001_create_ujikom_jadwal_tables.php`
  - Membuat tabel `ujikom_jadwal` dan `ujikom_persyaratan`
  - Foreign key `dibuat_oleh` → users (restrict)
  - Foreign key `ujikom_jadwal_id` → ujikom_jadwal (cascade)

### Models
- **BARU** `app/Models/UjikomJadwal.php`
  - fillable: judul, deskripsi, tanggal_mulai, tanggal_selesai, tempat, kuota, status, dibuat_oleh
  - casts: tanggal_mulai & tanggal_selesai sebagai date
  - hasMany: `persyaratan()` → UjikomPersyaratan (orderBy urutan)
  - belongsTo: `pembuat()` → User
  - Accessor `getStatusLabelAttribute()` → 'Draft' | 'Dipublikasikan' | 'Selesai'
  - Accessor `getBadgeStatusAttribute()` → 'secondary' | 'success' | 'dark'

- **BARU** `app/Models/UjikomPersyaratan.php`
  - fillable: ujikom_jadwal_id, nama_syarat, keterangan, file_contoh, urutan
  - belongsTo: `jadwal()` → UjikomJadwal

### Controller
- **BARU** `app/Http/Controllers/UjikomJadwalController.php`
  - `index()` — admin: semua jadwal + filter status; publik: hanya published (card grid)
  - `create()` — form tambah jadwal
  - `store()` — simpan jadwal + persyaratan, pilih simpan draft atau langsung publish
  - `show($id)` — detail jadwal; non-admin diblokir jika bukan published
  - `edit($id)` — form edit, hanya status draft
  - `update($id)` — update jadwal + hapus/buat ulang persyaratan
  - `destroy($id)` — hapus jadwal + file persyaratan (hanya draft)
  - `publish($id)` — ubah status draft → published
  - `selesaikan($id)` — ubah status published → selesai
  - Private helper: `simpanPersyaratan()`

### Views
- **BARU** `resources/views/coming_soon.blade.php`
  - Template halaman "Segera Hadir" untuk route placeholder
  - Menerima variabel `$judul`
  - Tombol kembali ke Dashboard

- **BARU** `resources/views/ujikom/jadwal/index.blade.php`
  - Tampilan berbeda: admin = tabel + filter, publik = card grid
  - Tombol aksi: Lihat, Edit, Publikasikan, Hapus, Selesaikan (kontekstual per status)

- **BARU** `resources/views/ujikom/jadwal/create.blade.php`
  - Form info jadwal + section persyaratan dinamis (tambah/hapus baris)
  - Dua tombol submit: "Simpan sebagai Draft" dan "Simpan & Publikasikan"

- **BARU** `resources/views/ujikom/jadwal/edit.blade.php`
  - Sama seperti create, pre-filled dengan data existing
  - Alert warning: persyaratan lama akan dihapus dan dibuat ulang

- **BARU** `resources/views/ujikom/jadwal/show.blade.php`
  - Header: info lengkap jadwal + badge status
  - Panel persyaratan: tabel + link download file contoh
  - Panel peserta: placeholder kosong (modul pendaftaran belum dibuat)
  - Tombol aksi admin: Edit, Publikasikan, Hapus, Selesaikan (per status)

### Routes
- **UBAH** `routes/web.php`
  - Tambah `use App\Http\Controllers\UjikomJadwalController`
  - Tambah group `ujikom/jadwal` (prefix, name `ujikom.jadwal.`) — ditempatkan **SEBELUM** group `ujikom/{id}` untuk mencegah konflik wildcard
  - Tambah 6 route placeholder coming soon

```
GET    /ujikom/jadwal               ujikom.jadwal.index
GET    /ujikom/jadwal/create        ujikom.jadwal.create  [admin|super_admin]
POST   /ujikom/jadwal               ujikom.jadwal.store   [admin|super_admin]
GET    /ujikom/jadwal/{id}          ujikom.jadwal.show
GET    /ujikom/jadwal/{id}/edit     ujikom.jadwal.edit    [admin|super_admin]
PUT    /ujikom/jadwal/{id}          ujikom.jadwal.update  [admin|super_admin]
DELETE /ujikom/jadwal/{id}          ujikom.jadwal.destroy [admin|super_admin]
POST   /ujikom/jadwal/{id}/publish      ujikom.jadwal.publish    [admin|super_admin]
POST   /ujikom/jadwal/{id}/selesaikan   ujikom.jadwal.selesaikan [admin|super_admin]

GET    /ujikom/permohonan           ujikom.permohonan.index  [Coming Soon]
GET    /ujikom/online               ujikom.online.index      [Coming Soon]
GET    /ujikom/hasil                ujikom.hasil.index       [Coming Soon]
GET    /karir                       karir.index              [Coming Soon]
GET    /karir/diklat                karir.diklat.index       [Coming Soon]
GET    /karir/analitik              karir.analitik.index     [Coming Soon]
```

---

## 4. Catatan Teknis

### Penempatan Route (Kritis!)
Route `ujikom/jadwal` **wajib** ditempatkan sebelum group `Route::prefix('ujikom')` yang mengandung `/{id}`. Jika tidak, URL `/ujikom/jadwal` akan tertangkap sebagai `ujikom/{id}` dengan id = "jadwal".

### Upload File Persyaratan
- Disimpan di `storage/app/public/ujikom/persyaratan/`
- Diakses via `asset('storage/ujikom/persyaratan/...')`
- Format yang diterima: pdf, doc, docx, xlsx, xls, jpg, jpeg, png
- Maksimum: 5MB per file
- Saat update jadwal: file lama dihapus dari disk sebelum buat baru

### Tidak Ada Soft Delete
Tabel `ujikom_jadwal` dan `ujikom_persyaratan` tidak menggunakan soft delete (sesuai kebutuhan — hapus permanen).

---

## 5. Testing Checklist

- [x] Migration berjalan tanpa error
- [x] Semua 9 route ujikom.jadwal.* terdaftar
- [x] Semua 6 route placeholder terdaftar
- [x] Route `ujikom/jadwal` tidak konflik dengan `ujikom/{id}`
- [ ] Admin bisa buat jadwal (draft)
- [ ] Admin bisa publikasikan jadwal
- [ ] Admin bisa tandai selesai
- [ ] Operator/viewer hanya melihat jadwal published (card grid)
- [ ] Persyaratan tersimpan dengan benar
- [ ] Upload file persyaratan berfungsi
- [ ] Link download file contoh berfungsi
- [ ] Halaman coming_soon muncul untuk 6 menu placeholder

---

## Versi 1.5.3 - Restrukturisasi Menu Sidebar
**Tanggal:** 22 Juni 2026
**Status:** Selesai ✅

---

## Ringkasan

Menu sidebar direstrukturisasi menjadi dua dropdown baru: **Kompetensi JFT** (menggantikan Uji Kompetensi) dan **Pengembangan Karir JFT** (menggantikan Pertimbangan Pengangkatan). Menu lama dicomment, bukan dihapus. Header "ADMINISTRASI" sekarang muncul untuk `admin|super_admin`.

---

## Perubahan

### 1. Menu "Uji Kompetensi" → "Kompetensi JFT" (Dropdown)
**File:** `resources/views/layouts/users/master.blade.php`

Menu lama dicomment. Digantikan dropdown dengan 4 submenu:
| Submenu | Route | Status |
|---------|-------|--------|
| Pengumuman Jadwal Ujikom | `ujikom.jadwal.index` | ✅ Aktif |
| Pendaftaran Ujikom | `ujikom.permohonan.index` | 🔜 Segera |
| Uji Kompetensi | `ujikom.online.index` | 🔜 Segera |
| Hasil Uji Kompetensi | `ujikom.hasil.index` | 🔜 Segera |

### 2. Menu "Pertimbangan Pengangkatan" → "Pengembangan Karir JFT" (Dropdown)
Menu lama dicomment. Digantikan dropdown dengan 4 submenu:
| Submenu | Route | Status |
|---------|-------|--------|
| Tabel Pengembangan Karir | `karir.index` | 🔜 Segera |
| Riwayat Diklat | `karir.diklat.index` | 🔜 Segera |
| Pertimbangan Pengangkatan | `pengangkatan.index` | ✅ Aktif |
| Analitik Pengembangan | `karir.analitik.index` | 🔜 Segera |

### 3. Header ADMINISTRASI
Dipindahkan ke dalam `@role('admin|super_admin')` sehingga admin juga melihat header sebelum menu Laporan (sebelumnya hanya muncul untuk super_admin).

### 4. Tampilan Badge "Segera"
Submenu yang belum aktif menggunakan:
- `style="pointer-events:none; opacity:0.55;"` untuk disable
- Badge kuning kecil `<span class="badge badge-warning">Segera</span>`

---

## Versi 1.5.2 - Import Data Unit Kerja dari Excel
**Tanggal:** 6 April 2026
**Status:** Selesai ✅

---

## Ringkasan

Import data Unit Kerja dari file Excel menggunakan Artisan Command yang menghasilkan seeder PHP secara otomatis.

---

## Detail Import

### Perintah yang Digunakan
```bash
# 1. Copy file Excel ke folder import
# file: storage/app/import/unitkerja.xlsx

# 2. Generate seeder dari Excel
php artisan unitkerja:make-seeder-from-excel --file=storage/app/import/unitkerja.xlsx

# 3. Jalankan seeder untuk import ke database
php artisan db:seed --class=UnitKerjaFromExcelSeeder20260406003736
```

### Hasil Import
| Metric | Value |
|--------|-------|
| Total Data Terimport | 523 unit kerja |
| File Seeder | `database/seeders/UnitKerjaFromExcelSeeder20260406003736.php` |

---

## Data yang Tidak Masuk (16 baris)

### Tidak Ditemukan di Database (2 baris)
| Baris | Kab/Kota | Keterangan |
|-------|---------|------------|
| 16 | Kota Tanjung Pinang | Tidak ada di tabel regencies |
| 31 | Kota Pangkal Pinang | Tidak ada di tabel regencies |

**Aksi:** Perlu ditambahkan manual ke tabel `regencies` atau update master data

---

### Data Ambigu (Dipilih Otomatis) - 14 baris

| Baris | Kab/Kota | Dipilih | Province |
|-------|---------|---------|----------|
| 33 | Kabupaten Serang | KOTA (id=271) | Banten |
| 45 | Kabupaten Bekasi | KOTA (id=181) | Jawa Barat |
| 46 | Kabupaten Bogor | KOTA (id=182) | Jawa Barat |
| 61 | Kabupaten Magelang | KOTA (id=217) | Jawa Tengah |
| 63 | Kabupaten Pekalongan | KOTA (id=218) | Jawa Tengah |
| 65 | Kabupaten Semarang | KOTA (id=220) | Jawa Tengah |
| 81 | Kabupaten Madiun | KOTA (id=260) | Jawa Timur |
| 83 | Kabupaten Mojokerto | KOTA (id=262) | Jawa Timur |
| 85 | Kabupaten Pasuruan | KOTA (id=263) | Jawa Timur |
| 92 | Kabupaten Blitar | KOTA (id=258) | Jawa Timur |
| 93 | Kabupaten Kediri | KOTA (id=259) | Jawa Timur |
| 105 | Kabupaten Kupang | KOTA (id=451) | Nusa Tenggara Timur |
| 119 | Kabupaten Gorontalo | KOTA (id=350) | Gorontalo |

**Catatan:** Data ambigu terjadi karena nama sama ada di tabel KABUPATEN dan KOTA. Command memilih KOTA secara default. Sebaiknya cek dan perbaiki manual.

---

## Cara Kerja Command Import

### Alur Kerja
```
1. Copy file Excel (.xlsx/.xls/.csv) ke: storage/app/import/
2. Jalankan command: php artisan unitkerja:make-seeder-from-excel --file=storage/app/import/[namafile].xlsx
3. Command akan:
   - Baca file Excel
   - Parse header dengan alias support
   - Resolve Province + Kab/Kota ke Regency ID
   - Generate file seeder PHP di: database/seeders/
   - Tampilkan warning jika ada data bermasalah
4. Jalankan seeder: php artisan db:seed --class=NamaSeeder
```

### Opsi Command
| Opsi | Default | Keterangan |
|------|---------|------------|
| `--file=` | storage/app/import/unitkerja.xlsx | Path file Excel |
| `--class=` | Auto (timestamp) | Nama class seeder |
| `--sheet=0` | 0 (sheet pertama) | Index sheet Excel |

### Alias Header yang Didukung
```
nama_unit, nama_unit_kerja, nama_rumahsakit, unit_kerja
alamat, no_telp, telepon
provinsi, province
kab_kota, kab/kota, kabupaten_kota, kota/kab
latitude, lat, longitude, long, lng
matra, instansi, no_rs, kode_unit
```

### Fitur Command
- **Auto-resolve:** Provinsi + Kab/Kota otomatis cocokkan
- **Upsert:** Jika data sama ada, akan di-update (bukan duplikat)
- **Alias:** Terima berbagai nama kolom Excel
- **Normalisasi:** Matra, Instansi, Province name
- **Warning:** Tampilkan baris bermasalah untuk ditinjau

---

## Command Import untuk Modul Lain

| Modul | Command | Keterangan |
|-------|---------|------------|
| Unit Kerja | `php artisan unitkerja:make-seeder-from-excel` | Generate seeder Unit Kerja |
| Formasi | `php artisan generate:formasi` | Generate seeder Formasi |
| SDM/Pegawai | `php artisan generate:sdm` | Generate seeder Pegawai JFT |

---

## Versi 1.5.1 - Perbaikan & Layout Pertimbangan Pengangkatan
**Tanggal:** 16 Maret 2026
**Status:** Dalam Pengembangan 🔄

---

## Ringkasan

Perbaikan dan penyesuaian layout modul Pertimbangan Pengangkatan untuk memperbaiki dropdown pemilihan karyawan dan menyesuaikan tampilan dengan modul Uji Kompetensi.

---

## Perubahan

### 1. Perbaikan AJAX getPegawai
- Route baru: `/pengangkatan/get-pegawai` untuk load data karyawan berdasarkan unit kerja
- Response JSON sekarang menggunakan format `id` dan `text` untuk kompatibilitas Select2
- Perbaikan format response controller untuk dropdown Select2

### 2. Perubahan Layout Form Create (In Progress 🔄)
- Layout diubah agar sama dengan modul Uji Kompetensi:
  - Dropdown pilih Pegawai dipindahkan ke atas tabel
  - User pilih Unit Kerja → dropdown Pegawai ter-populate via AJAX
  - User pilih Pegawai dari dropdown → klik "Tambah" → data masuk ke tabel
- Tabel sekarang menampilkan: Nama, NIP, Jabatan Asal, Jenjang Asal, Jabatan Tujuan, Jenjang Tujuan, Unit Kerja Tujuan, Validasi, Aksi

### 3. Perbaikan JavaScript
- Perbaikan variabel JavaScript (typo: `letpegawaiId` → `letPegawaiId`)
- Penambahan event listener untuk tombol Tambah menggunakan jQuery
- Perbaikan inisialisasi Select2 untuk menghindari konflik

### 4. Masalah yang Sedang Dikerjakan
- [ ] Dropdown pemilihan Pegawai: dropdown muncul, data ter-load, tapi tidak bisa diklik/dipilih
- [ ] Setelah dipilih, data belum masuk ke tabel

---

## Versi 1.5.0 - Modul Pertimbangan Pengangkatan JFT
**Tanggal:** 13 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Modul baru untuk mengelola pertimbangan pengangkatan Jabatan Fungsional Transportasi yang terintegrasi dengan modul Uji Kompetensi, data Pegawai, dan data Formasi. Modul ini memiliki 9 tahapan workflow mulai dari Draft hingga Selesai dengan validasi otomatis formasi dan hasil uji kompetensi.

---

## 1. Fitur Utama

### Workflow 9 Tahapan
1. **Draft** - Operator dapat mengedit dan menghapus
2. **Diajukan** - Menunggu verifikasi admin
3. **Diverifikasi** - Admin verifikasi permohonan
4. **Draft Surat** - Buat draft Surat Pertimbangan PDF
5. **Paraf Katim** - Konfirmasi paraf Kepala Tim
6. **Paraf Kabid** - Konfirmasi paraf Kepala Bidang
7. **Tanda Tangan** - Konfirmasi tanda tangan Kepala Pusat
8. **Penomoran** - Input nomor surat dari TU
9. **Selesai** - Otomatis update data pegawai

### Validasi Otomatis
- **Validasi Formasi**: Mengecek sisa kuota formasi (kuota - terisi) secara real-time via AJAX
- **Validasi Ujikom**: Mengecek hasil uji kompetensi terbaru pegawai

### 3 Jalur Pengangkatan
- **Inpasing** - Pengangkatan inpasing
- **Promosi** - Promosi jenjang
- **Perpindahan Jabatan** - Pindah jabatan/unit kerja

### Integrasi Data
- Terintegrasi dengan modul Uji Kompetensi
- Terintegrasi dengan data Pegawai (SDM)
- Terintegrasi dengan data Formasi
- Otomatis update data pegawai saat status Selesai
- Otomatis recalculate status_formasi pegawai lain

---

## 2. Struktur Database

### Tabel Baru

#### `pengangkatan_permohonan`
Menyimpan data permohonan pertimbangan pengangkatan.
- `nomor_permohonan` - Format: PANGKAT/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT]
- `jalur` - inpasing, promosi, perpindahan_jabatan
- `unit_kerja_id` - Foreign key ke rumahsakits
- `file_surat_permohonan` - Upload surat permohonan (PDF)
- `tanggal_permohonan` - Tanggal permohonan
- `status` - 9 status workflow
- `catatan_verifikator` - Catatan dari verifikator
- `created_by` - Foreign key ke users

#### `pengangkatan_peserta`
Menyimpan data peserta dalam permohonan.
- `pengangkatan_permohonan_id` - Foreign key ke pengangkatan_permohonan
- `pegawai_id` - Foreign key ke sumber_daya_manusia
- `jabatan_asal`, `jenjang_asal`, `unit_kerja_asal` - Data asal pegawai
- `jabatan_tujuan_id` - Foreign key ke formasi_jabatan
- `jenjang_tujuan`, `unit_kerja_tujuan_id` - Data tujuan
- `ujikom_peserta_id` - Foreign key ke ujikom_peserta (nullable)
- `status_validasi_formasi` - tersedia / tidak_tersedia
- `status_validasi_ujikom` - memenuhi / tidak_memenuhi
- `catatan` - Catatan tambahan

#### `pengangkatan_surat`
Menyimpan data surat pertimbangan yang digenerate.
- `pengangkatan_permohonan_id` - Foreign key ke pengangkatan_permohonan
- `nomor_surat` - Nomor surat dari TU
- `file_path` - Path file PDF surat pertimbangan
- `dibuat_oleh` - Foreign key ke users
- `tanggal_dibuat` - Timestamp pembuatan

---

## 3. File yang Dibuat/Diubah

### Migration
**BARU** `database/migrations/2026_03_13_create_pengangkatan_tables.php`
- Membuat 3 tabel: pengangkatan_permohonan, pengangkatan_peserta, pengangkatan_surat
- Menambahkan foreign keys dan indexes

### Models
**BARU** `app/Models/PengangkatanPermohonan.php`
- Relasi ke UnitKerja, User, Peserta, Surat
- Method helper: generateNomorPermohonan(), numberToRoman()
- Method cek status: bisaDiedit(), bisaDihapus(), bisaDiajukan(), dll.
- Accessor untuk label dan badge color

**BARU** `app/Models/PengangkatanPeserta.php`
- Relasi ke Permohonan, Pegawai, JabatanTujuan, UnitKerjaTujuan, UjikomPeserta
- Static method: cekFormasi(), cekUjikom()
- Accessor untuk badge color dan label

**BARU** `app/Models/PengangkatanSurat.php`
- Relasi ke Permohonan dan User
- Scope latest()

### Controller
**BARU** `app/Http/Controllers/PengangkatanController.php`
- 18 method: index, create, store, show, edit, update, destroy
- 8 method workflow: ajukan, verifikasi, tolak, buatDraftSurat, konfirmasiParafKatim, konfirmasiParafKabid, konfirmasiTtd, selesaikan
- 2 method input nomor: inputNomor, simpanNomor
- 2 method AJAX/export: validasiPeserta, exportPdf

### Views
**BARU** `resources/views/pengangkatan/index.blade.php`
- Tabel daftar permohonan dengan filter (jalur, status, unit kerja, tahun)
- Badge warna untuk jalur dan status
- Tombol aksi kontekstual (view, edit, delete, export PDF)
- DataTables dengan pagination

**BARU** `resources/views/pengangkatan/create.blade.php`
- Form input permohonan (jalur, unit kerja, tanggal, upload surat)
- Tabel peserta batch dengan input dinamis
- AJAX validation real-time untuk formasi dan ujikom
- Select2 untuk dropdown pegawai dan formasi
- Tombol Simpan Draft & Simpan + Ajukan

**BARU** `resources/views/pengangkatan/edit.blade.php`
- Mirip dengan create tapi dengan data yang sudah ada
- Hanya bisa diedit jika status = draft

**BARU** `resources/views/pengangkatan/show.blade.php`
- Header info permohonan (nomor, jalur, unit kerja, tanggal, status)
- Timeline stepper 9 langkah dengan visual progress
- Tabel peserta dengan detail jabatan asal/tujuan dan validasi
- Catatan verifikator (jika ada)
- Panel aksi kontekstual sesuai status & role
- Modal tolak permohonan

**BARU** `resources/views/pengangkatan/nomor.blade.php`
- Form input nomor surat dari TU
- Link ke draft surat pertimbangan

**BARU** `resources/views/pengangkatan/pdf/surat_pertimbangan.blade.php`
- Template PDF Surat Pertimbangan dengan kop surat
- Tabel peserta lengkap
- Footer tanda tangan (Verifikator & Kepala Pusat)

**BARU** `resources/views/pengangkatan/pdf/detail.blade.php`
- Template PDF untuk export detail permohonan
- Ringkasan informasi dan validasi

### Routes
**UBAH** `routes/web.php`
- Import PengangkatanController
- Tambahkan route group prefix `/pengangkatan` dengan 20 route:
  - CRUD: index, create, store, show, edit, update, destroy
  - Workflow: ajukan, verifikasi, tolak, draft-surat, paraf-katim, paraf-kabid, ttd
  - Nomor: nomor (GET), simpan-nomor (POST)
  - Selesaikan: selesaikan
  - AJAX/Export: validasi-peserta (POST), export (GET)
- Middleware: permission untuk setiap route

### Sidebar
**UBAH** `resources/views/layouts/users/master.blade.php`
- Tambah menu "Pertimbangan Pengangkatan" di bawah "Uji Kompetensi"
- Icon: fa-file-signature
- Visible untuk: operator, admin, super_admin

### Helpers
**UBAH** `app/helpers.php`
- Tambah function `formatNomorPermohonanPengangkatan()`
- Format: PANGKAT/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT 4 digit]

---

## 4. Permissions Baru

Tambahkan permissions berikut ke database (via seeder atau manual):

```php
// View
'view pengangkatan'
'create pengangkatan'
'edit pengangkatan'
'delete pengangkatan'
'verifikasi pengangkatan'
```

**Mapping ke Role:**
- **Operator**: view, create, edit, delete
- **Admin**: view, create, edit, delete, verifikasi
- **Super Admin**: view, create, edit, delete, verifikasi
- **Viewer**: - (tidak memiliki akses)

---

## 5. Alur Penggunaan

### Operator
1. Buat permohonan baru → Input data permohonan
2. Tambah peserta → Pilih pegawai + jabatan tujuan
3. Sistem otomatis validasi formasi & ujikom (real-time)
4. Simpan draft atau Simpan + Ajukan

### Admin / Super Admin
1. Verifikasi permohonan → Review data & peserta
2. Buat draft Surat Pertimbangan → Generate PDF otomatis
3. Konfirmasi paraf Katim → Paraf Kabid → Tanda Tangan
4. Input nomor surat dari TU
5. Selesaikan permohonan → Otomatis update data pegawai

---

## 6. Catatan Teknis

### Validasi Real-Time (AJAX)
- Endpoint: `POST /pengangkatan/validasi-peserta`
- Parameter: `pegawai_id`, `jabatan_tujuan_id`, `unit_kerja_tujuan_id`
- Response: JSON dengan status formasi & ujikom

### Update Otomatis Data Pegawai
Saat permohonan diselesaikan (status → selesai):
- Update `formasi_jabatan_id` dan `unit_kerja_id` pegawai
- Recalculate `status_formasi` pegawai lain di jabatan lama
- Recalculate `status_formasi` pegawai lain di jabatan baru

### Soft Delete
- Semua tabel menggunakan soft delete
- Hanya permohonan draft yang bisa dihapus

### Nomor Permohonan
- Format: PANGKAT/ROMAWI/TAHUN/URUT (4 digit)
- Contoh: PANGKAT/III/2026/0001
- Auto-regenerate jika tanggal permohonan berubah

---

## 7. Bugs & Limitasi yang Diketahui

### Tidak Dapat Ditimpa (Override)
- Formasi penuh tetap diizinkan (soft warning)
- Hasil ujikom belum/tidak lulus tetap diizinkan (soft warning)

### Tidak Dapat Diedit
- Permohonan dengan status selain draft tidak dapat diedit
- Untuk mengubah data, harus tolak → draft terlebih dahulu

---

## 8. Testing Checklist

- [x] Migration berjalan tanpa error
- [x] Model relasi berfungsi dengan benar
- [x] Create permohonan dengan 1+ peserta
- [x] Edit permohonan (hanya draft)
- [x] Hapus permohonan (hanya draft)
- [x] Ajukan permohonan (draft → diajukan)
- [x] Verifikasi permohonan (diajukan → diverifikasi)
- [x] Tolak permohonan (diajukan → draft + catatan)
- [x] Buat draft surat pertimbangan (diverifikasi → draft_surat + PDF)
- [x] Konfirmasi paraf katim (draft_surat → paraf_katim)
- [x] Konfirmasi paraf kabid (paraf_katim → paraf_kabid)
- [x] Konfirmasi ttd (paraf_kabid → tanda_tangan)
- [x] Input nomor surat (tanda_tangan → penomoran)
- [x] Selesaikan permohonan (penomoran → selesai + update pegawai)
- [x] Validasi formasi real-time (AJAX)
- [x] Validasi ujikom real-time (AJAX)
- [x] Export PDF detail permohonan
- [x] Export PDF surat pertimbangan
- [x] Role & permission berfungsi dengan benar
- [x] Menu sidebar muncul untuk role yang sesuai
- [x] Filter di halaman index berfungsi
- [x] DataTables pagination berfungsi

---

## Versi 1.4.1 - Bug Fix Modul Uji Kompetensi
**Tanggal:** 12 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Perbaikan bug pada modul Uji Kompetensi yang ditemukan saat pengujian alur lengkap. Dua perbaikan utama dilakukan: (1) Filter pegawai berdasarkan unit kerja sekarang berfungsi dengan benar menggunakan Select2, dan (2) Generate Berita Acara (Verifikasi & Hasil) tidak lagi error akibat karakter "/" pada nama file.

---

## 1. Bug yang Diperbaiki

### Bug #1: Filter Pegawai Tidak Berfungsi
**Gejala:** Dropdown pegawai tetap menampilkan semua pegawai meskipun unit kerja sudah dipilih.

**Penyebab:** Select2 tidak menghormati metode `.hide()` pada elemen option asli karena Select2 membuat dropdown-nya sendiri secara terpisah.

**Solusi:**
- Menghancurkan Select2 dengan `select2('destroy')` sebelum melakukan filter
- Memanipulasi properti `disabled` pada elemen option asli
- Re-initialize Select2 dengan fungsi `templateResult` khusus yang menyembunyikan option yang disabled

**File yang Dimodifikasi:**
- `resources/views/ujikom/create.blade.php`
  - Memperbaiki fungsi `filterDanTampilkanPegawai()` untuk menggunakan pendekatan Select2 yang benar
  - Menghapus kode debugging yang sudah tidak diperlukan

**Testing:**
- Filter berhasil memfilter 77 pegawai yang cocok dari total 3927 pegawai
- Dropdown Select2 hanya menampilkan pegawai dari unit kerja yang dipilih

### Bug #2: Generate Berita Acara Error
**Gejala:** Error saat generate BA dengan pesan "The filename and the fallback cannot contain the "/" and "\" characters."

**Penyebab:** Nomor permohonan dengan format "UJIKOM/III/2026/001" mengandung karakter "/" yang tidak valid untuk nama file.

**Solusi:**
- Mengganti karakter "/" dengan "-" menggunakan `str_replace('/', '-', $nomor_permohonan)` sebelum digunakan sebagai nama file
- Diterapkan pada 3 fungsi yang menggenerate PDF: `generateBeritaAcaraVerifikasi()`, `generateBeritaAcaraHasil()`, dan `exportPdf()`

**File yang Dimodifikasi:**
- `app/Http/Controllers/UjikomController.php`
  - Line 575: `generateBeritaAcaraVerifikasi()` - Menambahkan sanitasi nama file
  - Line 606: `generateBeritaAcaraHasil()` - Menambahkan sanitasi nama file
  - Line 516: `exportPdf()` - Menambahkan sanitasi nama file

**Testing:**
- BA Verifikasi berhasil didownload dengan nama file: `ba-verifikasi-UJIKOM-III-2026-001.pdf`
- BA Hasil berhasil didownload dengan nama file: `ba-hasil-UJIKOM-III-2026-001.pdf`
- Export PDF berhasil didownload dengan nama file: `permohonan-ujikom-UJIKOM-III-2026-001.pdf`

### Bug #3: Halaman Edit Error (Null Property Access)
**Gejala:** Error "Attempt to read property 'nama_rumahsakit' on null" saat membuka halaman edit permohonan.

**Penyebab:** Ada pegawai yang tidak memiliki relasi ke unit kerja atau formasi, menyebabkan error ketika kode mencoba mengakses property null.

**Solusi:**
- Menggunakan null-safe operator (`?->`) untuk mencegah error saat property null
- Mengubah pendekatan filter dari `data-unit-kerja-id` (single value) ke `data-unit-kerja-ids` (comma-separated values)
- Menggunakan pendekatan Select2 yang sama seperti create.blade.php

**File yang Dimodifikasi:**
- `resources/views/ujikom/edit.blade.php`
  - Line 83-116: Mengubah logic PHP untuk menangani pegawai tanpa unit kerja
  - Line 102: Mengganti `data-unit-kerja-id` dengan `data-unit-kerja-ids`
  - Fungsi `filterPegawaiByUnitKerja()`: Menggunakan pendekatan Select2 yang benar (destroy → filter → reinitialize)

**Testing:**
- Halaman edit berhasil ditampilkan tanpa error
- Filter pegawai berfungsi dengan benar

### Bug #4: Duplicate Entry Error Saat Simpan Edit
**Gejala:** Error "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '2-5079' for key 'ujikom_peserta.ujikom_peserta_ujikom_permohonan_id_pegawai_id_unique'" saat menyimpan hasil edit.

**Penyebab:** Kode menggunakan `delete()` (soft delete) untuk menghapus peserta lama, lalu menginsert pegawai yang sama. Karena soft delete hanya menandai record sebagai deleted (deleted_at != NULL), constraint unique tetap terpicu ketika insert pegawai yang sama. Masalah diperparah karena `pluck()` tidak mengembalikan record yang sudah soft-deleted.

**Solusi:**
- Menggunakan `withTrashed()` untuk mendapatkan SEMUA peserta termasuk yang soft-deleted
- Implement sync logic: restore jika sedang soft-deleted, force delete jika tidak ada di list baru
- Check dengan `withTrashed()` sebelum insert untuk mencegah duplikasi

**File yang Dimodifikasi:**
- `app/Http/Controllers/UjikomController.php`
  - Method `update()` (line 220-245): Mengganti logic sync peserta dengan pendekatan yang lebih robust

**Perubahan Kode:**
```php
// Get ALL existing peserta (including soft-deleted)
$allExistingPeserta = UjikomPeserta::withTrashed()
    ->where('ujikom_permohonan_id', $permohonan->id)
    ->get();

foreach ($allExistingPeserta as $existingPeserta) {
    // Hapus permanen jika tidak ada di list baru
    if (!in_array($existingPeserta->pegawai_id, $newPesertaIds)) {
        $existingPeserta->forceDelete();
    }
    // Restore jika sedang soft-deleted tapi ada di list baru
    elseif ($existingPeserta->trashed()) {
        $existingPeserta->restore();
    }
}

// Add new peserta (check dengan withTrashed)
foreach ($newPesertaIds as $pegawaiId) {
    $peserta = UjikomPeserta::withTrashed()
        ->where('ujikom_permohonan_id', $permohonan->id)
        ->where('pegawai_id', $pegawaiId)
        ->first();

    if (!$peserta) {
        UjikomPeserta::create([...]);
    }
}
```

**Testing:**
- Edit permohonan dengan peserta yang sama berhasil tanpa error
- Edit dan menghapus sebagian peserta berhasil
- Edit dan menambah peserta baru berhasil

---

## 2. Perubahan Kode

### resources/views/ujikom/create.blade.php

**Fungsi `filterDanTampilkanPegawai()` - Perbaikan:**
```javascript
// Sebelum: Tidak berhasil menyembunyikan option dari Select2
$('.pegawai-option').each(function() {
  // ...
  if (isMatch) {
    $(this).prop('disabled', false).show();
  } else {
    $(this).prop('disabled', true).hide();
  }
});

// Sesudah: Re-initialize Select2 dengan templateResult khusus
$('#pegawaiSelect').select2('destroy');

$('.pegawai-option').each(function() {
  // ...
  if (isMatch) {
    $(this).prop('disabled', false);
  } else {
    $(this).prop('disabled', true);
  }
});

$('#pegawaiSelect').select2({
  theme: 'bootstrap4',
  width: '100%',
  placeholder: '-- Pilih Pegawai --',
  allowClear: true,
  templateResult: function(result) {
    if (!result.id) return result.text;
    var $option = $(result.element);
    if ($option.prop('disabled')) {
      return null; // Sembunyikan option disabled
    }
    return result.text;
  }
});
```

### app/Http/Controllers/UjikomController.php

**Fungsi `generateBeritaAcaraVerifikasi()` - Line 575:**
```php
// Sebelum:
$fileName = 'ba-verifikasi-' . $permohonan->nomor_permohonan . '.pdf';

// Sesudah:
$nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
$fileName = 'ba-verifikasi-' . $nomorPermohonanSafe . '.pdf';
```

**Fungsi `generateBeritaAcaraHasil()` - Line 606:**
```php
// Sebelum:
$fileName = 'ba-hasil-' . $permohonan->nomor_permohonan . '.pdf';

// Sesudah:
$nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
$fileName = 'ba-hasil-' . $nomorPermohonanSafe . '.pdf';
```

**Fungsi `exportPdf()` - Line 516:**
```php
// Sebelum:
$filename = 'permohonan-ujikom-' . $permohonan->nomor_permohonan . '.pdf';

// Sesudah:
$nomorPermohonanSafe = str_replace('/', '-', $permohonan->nomor_permohonan);
$filename = 'permohonan-ujikom-' . $nomorPermohonanSafe . '.pdf';
```

---

## 3. Changelog Summary

| Versi | Tanggal | Deskripsi |
|-------|---------|-----------|
| 1.4.1 | 12 Mar 2026 | Bug Fix: Filter pegawai, Generate BA, Edit page, Duplicate entry |
| 1.4.0 | 12 Mar 2026 | Modul Uji Kompetensi |
| 1.3.0 | 11 Mar 2026 | Laporan Terpadu (PAUSED - Error belum teridentifikasi) |
| 1.2.0 | 10 Mar 2026 | Implementasi Status Formasi (Over Kuota Diizinkan) |
| 1.1.0 | 10 Mar 2026 | Implementasi Spatie Laravel Permission |
| 1.0.0 | - | Versi awal dengan role sederhana (admin/user) |

---

## Versi 1.1.0 - Implementasi User Role & Permission System
**Tanggal:** 10 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi sistem Role & Permission menggunakan **Spatie Laravel Permission** untuk menggantikan sistem role sederhana yang sebelumnya ada. Sistem ini mendukung 4 role dengan hak akses berbeda dan halaman manajemen user untuk super_admin.

---

## 1. File yang Dibuat (Baru)

### Database & Seeders

| File Path | Deskripsi |
|-----------|-----------|
| `database/migrations/2025_03_10_add_status_to_users_table.php` | Migration untuk menambahkan kolom `status` (enum: active/inactive) dan menghapus kolom `role` dari tabel users |
| `database/migrations/2026_03_10_033217_create_permission_tables.php` | Migration otomatis dari Spatie untuk membuat tabel permissions, roles, role_has_permissions, model_has_permissions, model_has_roles |
| `database/seeders/PermissionSeeder.php` | Seeder untuk membuat 17 permissions yang diperlukan sistem |
| `database/seeders/RoleSeeder.php` | Seeder untuk membuat 4 role (super_admin, admin, operator, viewer) dengan permissions masing-masing |

### Controllers

| File Path | Deskripsi |
|-----------|-----------|
| `app/Http/Controllers/UserController.php` | Controller untuk manajemen user dengan methods: index, create, store, edit, update, destroy, resetPassword |

### Views - Manajemen User

| File Path | Deskripsi |
|-----------|-----------|
| `resources/views/users/manajemen-user/index.blade.php` | Halaman daftar user dengan DataTables, badge role, tombol aksi |
| `resources/views/users/manajemen-user/form.blade.php` | Form tambah/edit user dengan select2 dropdown untuk role dan status |

---

## 2. File yang Dimodifikasi

### Models

| File Path | Perubahan |
|-----------|-----------|
| `app/Models/User.php` | - Menambahkan trait `HasRoles` dari Spatie<br>- Menghapus field `role` dari fillable |

### Controllers

| File Path | Perubahan |
|-----------|-----------|
| `app/Http/CentralController.php` | **Method `LoginAksi`:**<br>- Menggunakan `Auth::user()->fresh()` untuk reload user dengan roles<br>- Cek jika user belum punya role → assign 'viewer' otomatis<br>- Redirect ke `route('user.peta')` bukan 'admin' atau 'user'<br><br>**Method `registerAksi`:**<br>- Menambahkan field `status` dengan nilai 'active'<br>- Assign default role 'viewer' untuk user baru |

### HTTP Middleware

| File Path | Perubahan |
|-----------|-----------|
| `app/Http/Kernel.php` | Mendaftarkan 3 middleware baru dari Spatie:<br>- `role` → RoleMiddleware<br>- `permission` → PermissionMiddleware<br>- `role_or_permission` → RoleOrPermissionMiddleware |

### Routes

| File Path | Perubahan |
|-----------|-----------|
| `routes/web.php` | - Menambahkan import `UserController`<br>- Menambahkan route group untuk manajemen user (hanya super_admin)<br>- Dashboard: menghapus middleware `permission:view dashboard` agar semua user bisa akses<br>- Export: tetap menggunakan middleware `permission:export data` |

### Views - Layout

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/layouts/users/master.blade.php` | **Navbar:**<br>- Menampilkan nama user dan badge role dengan warna berbeda<br>- super_admin = merah, admin = biru, operator = kuning, viewer = hijau<br><br>**Sidebar:**<br>- Mengganti `@if (Auth::user()->role == 'admin')` dengan `@role('super_admin')`<br>- Mengganti conditional role lama dengan `@can('view ...')` directive<br>- Menambahkan menu "Manajemen User" (hanya untuk super_admin)<br>- Menu Dashboard menggunakan `route('user.peta')` |

### Views - Permission-based Buttons

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/users/index.blade.php` | Unit Kerja - Menambahkan `@can` directive:<br>- `@can('create unit kerja')` untuk tombol Tambah<br>- `@can('edit unit kerja')` untuk tombol Edit<br>- `@can('delete unit kerja')` untuk tombol Delete |
| `resources/views/formasi_jabatan/index.blade.php` | Formasi - Menambahkan `@can` directive:<br>- `@can('create formasi')` untuk tombol Tambah & Import<br>- `@can('edit formasi')` untuk tombol Edit Grup |
| `resources/views/sdm/index.blade.php` | Pegawai JFT - Menambahkan `@can` directive:<br>- `@can('create pegawai')` untuk tombol Tambah & Import<br>- `@can('edit pegawai')` untuk tombol Edit<br>- `@can('delete pegawai')` untuk tombol Hapus |

### Seeders

| File Path | Perubahan |
|-----------|-----------|
| `database/seeders/UserSeeder.php` | - Menghapus field 'role' dari userData<br>- Menambahkan field 'status'<br>- Membuat 4 akun default dengan role Spatie<br>- Menggunakan `Hash::make()` untuk password<br>- Assign role dengan `assignRole()` setelah user dibuat |

---

## 3. Struktur Role & Permission

### Role yang Diimplementasikan

| Role | Deskripsi | Badge Color |
|------|-----------|-------------|
| **super_admin** | Akses penuh ke semua fitur + manajemen user | Merah (bg-danger) |
| **admin** | CRUD semua data (Unit Kerja, Formasi, Pegawai) | Biru (bg-primary) |
| **operator** | Hanya tambah/import data, tidak bisa edit/hapus | Kuning (bg-warning) |
| **viewer** | Hanya lihat + export, tidak bisa input/edit/hapus | Hijau (bg-success) |

### Permissions yang Dibuat

#### General Permissions (2)
- `view dashboard` - Melihat dashboard
- `export data` - Export Excel/PDF

#### Unit Kerja Permissions (4)
- `view unit kerja` - Melihat data Unit Kerja
- `create unit kerja` - Menambah Unit Kerja baru
- `edit unit kerja` - Mengedit data Unit Kerja
- `delete unit kerja` - Menghapus data Unit Kerja

#### Formasi Permissions (4)
- `view formasi` - Melihat data Formasi
- `create formasi` - Menambah/import Formasi baru
- `edit formasi` - Mengedit data Formasi
- `delete formasi` - Menghapus data Formasi

#### Pegawai JFT Permissions (4)
- `view pegawai` - Melihat data Pegawai JFT
- `create pegawai` - Menambah/import Pegawai JFT baru
- `edit pegawai` - Mengedit data Pegawai JFT
- `delete pegawai` - Menghapus data Pegawai JFT

#### User Management Permissions (1)
- `manage users` - Mengelola user (hanya super_admin)

### Mapping Role → Permissions

| Permission | super_admin | admin | operator | viewer |
|------------|-------------|-------|----------|--------|
| view dashboard | ✅ | ✅ | ✅ | ✅ |
| export data | ✅ | ✅ | ✅ | ✅ |
| view unit kerja | ✅ | ✅ | ✅ | ✅ |
| create unit kerja | ✅ | ✅ | ✅ | ❌ |
| edit unit kerja | ✅ | ✅ | ❌ | ❌ |
| delete unit kerja | ✅ | ✅ | ❌ | ❌ |
| view formasi | ✅ | ✅ | ✅ | ✅ |
| create formasi | ✅ | ✅ | ✅ | ❌ |
| edit formasi | ✅ | ✅ | ❌ | ❌ |
| delete formasi | ✅ | ✅ | ❌ | ❌ |
| view pegawai | ✅ | ✅ | ✅ | ✅ |
| create pegawai | ✅ | ✅ | ✅ | ❌ |
| edit pegawai | ✅ | ✅ | ❌ | ❌ |
| delete pegawai | ✅ | ✅ | ❌ | ❌ |
| manage users | ✅ | ❌ | ❌ | ❌ |

---

## 4. Akun Default untuk Testing

### Akun Production

| Email | Password | Role | Hak Akses |
|-------|----------|------|-----------|
| superadmin@pusbin.go.id | password123 | super_admin | Full access + manajemen user |
| admin@pusbin.go.id | password123 | admin | CRUD semua data |
| operator@pusbin.go.id | password123 | operator | Tambah/import data saja |
| viewer@pusbin.go.id | password123 | viewer | View + export saja |

### Catatan Keamanan
⚠️ **PENTING:** Ganti password default ini di production environment!

---

## 5. Perintah Artisan (Setup Ulang)

Jika perlu setup ulang dari awal, jalankan perintah berikut secara berurutan:

### 1. Install Package Spatie
```bash
composer require spatie/laravel-permission
```

### 2. Publish Migration & Config
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 3. Jalankan Migration
```bash
php artisan migrate
```

### 4. Jalankan Seeder
```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=UserSeeder
```

### 5. Clear Cache (Optional)
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## 6. Catatan Penting & Troubleshooting

### ⚠️ Warnings (Bukan Error)

#### PSR-4 Autoloading Warning
Saat install package, muncul warning:
```
Class Database\Seeders\RumahSakitSeeder located in ./database/seeders/RumahsakitSeeder.php does not comply with psr-4 autoloading standard
Class App\Models\JenjangJabatan located in ./app/Models/Jenjangjabatan.php does not comply with psr-4 autoloading standard
Class App\Models\Ujikompetensi located in ./app/Models/UjiKompetensi.php does not comply with psr-4 autoloading standard
```
**Penyebab:** Nama file tidak match dengan nama class (case sensitivity)
**Dampak:** Tidak ada, hanya warning. Class tetap bisa di-load.
**Solusi:** (Optional) Rename file agar sesuai dengan nama class, atau abaikan saja.

#### Security Vulnerability Advisory
```
Found 30 security vulnerability advisories affecting 8 packages
```
**Penyebab:** Ada security advisories di package existing (bukan dari Spatie)
**Dampak:** Tidak ada untuk implementasi role & permission ini
**Solusi:** Jalankan `composer audit` untuk melihat detail, dan update package jika perlu

### 🔧 Troubleshooting

#### Masalah 1: Blank Page Setelah Login
**Gejala:** Setelah klik tombol login, halaman menjadi putih
**Penyebab:** Kolom `role` sudah dihapus tapi CentralController masih mengaksesnya
**Solusi:** ✅ **Sudah diperbaiki** - LoginAksi sekarang menggunakan Spatie roles

#### Masalah 2: Route [user.dashboard.peta] not defined
**Gejala:** Error route tidak ditemukan
**Penyebab:** Nama route yang salah (harusnya `user.peta`)
**Solusi:** ✅ **Sudah diperbaiki** - Semua referensi route sudah dikoreksi

#### Masalah 3: User Lama Tidak Bisa Login
**Gejala:** User yang dibuat sebelum implementasi Spatie tidak bisa login
**Penyebab:** User lama belum punya role Spatie
**Solusi:** ✅ **Otomatis diperbaiki** - Saat login, user tanpa role otomatis diassign 'viewer'

#### Masalah 4: Permission Mismatch
**Gejala:** User dengan role tertentu tidak bisa mengakses fitur yang seharusnya bisa
**Penyebab:** Permission belum diassign ke role
**Solusi:** Cek database:
```sql
-- Cek role user
SELECT u.name, r.name FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id;

-- Cek permissions role
SELECT r.name, p.name FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
ORDER BY r.name, p.name;
```

### 📦 Package Version Info

| Package | Version | Catatan |
|---------|---------|---------|
| spatie/laravel-permission | 6.24.1 | Kompatibel dengan Laravel 10, PHP < 8.4 |
| PHP Version | < 8.4 | Jika upgrade ke PHP 8.4+, bisa upgrade ke v7.x |

---

## 7. Testing Checklist

Sebelum deploy ke production, pastikan:

### Login Test
- [ ] Login sebagai superadmin@pusbin.go.id → berhasil, ada menu Manajemen User
- [ ] Login sebagai admin@pusbin.go.id → berhasil, tidak ada menu Manajemen User
- [ ] Login sebagai operator@pusbin.go.id → berhasil, hanya ada tombol Tambah/Import
- [ ] Login sebagai viewer@pusbin.go.id → berhasil, tidak ada tombol aksi

### Permission Test
- [ ] Super admin bisa tambah, edit, hapus, dan reset password user
- [ ] Admin bisa CRUD data tapi tidak bisa manajemen user
- [ ] Operator hanya bisa tambah/import, tidak bisa edit/hapus
- [ ] Viewer hanya bisa lihat dan export

### Security Test
- [ ] Coba akses `/user/manajemen-user` sebagai admin → 403 Forbidden
- [ ] Coba akses URL edit sebagai operator → 403 Forbidden
- [ ] User baru dari register otomatis dapat role 'viewer'
- [ ] User lama yang login otomatis diassign role jika belum punya

---

## 8. Next Steps (Improvement)

### Recommended Updates
1. **Ganti password default** - Ubah password akun default untuk production
2. **Fix PSR-4 warnings** - (Optional) Rename file agar sesuai standar
3. **Add password validation** - Tambahkan policy untuk kekuatan password
4. **Implement logging** - Log aktivitas user (CRUD, login attempts)
5. **Add 2FA** - Pertimbangkan two-factor authentication untuk super_admin
6. **Rate limiting** - Tambahkan rate limiting untuk login attempts

### Future Enhancements
1. **Audit trail** - Catat siapa mengubah apa dan kapan
2. **Role expiration** - Role dengan masa berlaku tertentu
3. **IP whitelist** - Batasi akses super_admin berdasarkan IP
4. **Session management** - Force logout, revoke sessions
5. **Permission groups** - Group permissions untuk manajemen lebih mudah

---

## 9. Changelog Summary

| Versi | Tanggal | Deskripsi |
|-------|---------|-----------|
| 1.1.0 | 10 Mar 2026 | Implementasi Spatie Laravel Permission |
| 1.0.0 | - | Versi awal dengan role sederhana (admin/user) |

---

## Versi 1.2.0 - Implementasi Status Formasi (Over Kuota Diizinkan)
**Tanggal:** 10 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Sistem formasi diperbarui dengan pendekatan "Over Kuota Diizinkan". Pegawai tetap bisa ditambahkan meskipun kuota formasi sudah penuh, namun akan ditandai sebagai "Di Luar Formasi". Sistem ini lebih fleksibel dan memberikan transparansi penuh mengenai kelebihan kuota.

---

## 1. File yang Dibuat (Baru)

### Database Migrations

| File Path | Deskripsi |
|-----------|-----------|
| `database/migrations/2025_03_10_add_status_formasi_to_sdm_table.php` | Menambahkan kolom `status_formasi` (ENUM: 'terpenuhi', 'di_luar_formasi') ke tabel `sumber_daya_manusia` dengan default 'terpenuhi' |

---

## 2. File yang Dimodifikasi

### Models

| File Path | Perubahan |
|-----------|-----------|
| `app/Models/Sdmmodels.php` | Menambahkan `status_formasi` ke `$fillable` array |
| `app/Models/Formasijabatan.php` | **Perubahan 1:** `getSisaAttribute()` - Sekarang mengembalikan nilai NEGATIF jika over kuota (tidak dibatasi min 0)<br>**Perubahan 2:** Menambahkan `getSisaClassAttribute()` - Helper untuk CSS class berdasarkan nilai sisa |

### Controllers

| File Path | Perubahan |
|-----------|-----------|
| `app/Http/Controllers/SdmController.php` | **Method `index()`:** Menambahkan filter status formasi (terpenuhi/di_luar_formasi)<br><br>**Method `store()`:** Cek status formasi SEBELUM pegawai ditambahkan. Jika sisa <= 0, set status 'di_luar_formasi' dan tampilkan warning.<br><br>**Method `update()`:** Recalculate status untuk formasi lama dan baru saat pegawai pindah formasi.<br><br>**Method `destroy()`:** Recalculate status untuk pegawai lain di formasi yang sama.<br><br>**Method `restore()`:** Recalculate status setelah restore.<br><br>**Method `forceDelete()`:** Recalculate status setelah force delete.<br><br>**Method `recalculateStatusFormasi()`:** **BARU** - Private method untuk menghitung ulang status semua pegawai dalam formasi berdasarkan prioritas created_at |

### Controllers (Formasi)

| File Path | Perubahan |
|-----------|-----------|
| `app/Http/Controllers/FormasiJabatanController.php` | Menambahkan method `deleteGroup()` untuk menghapus semua formasi per unit & tahun |

### Routes

| File Path | Perubahan |
|-----------|-----------|
| `routes/web.php` | Menambahkan route: `Route::delete('formasi/delete-group', ...)` untuk hapus formasi per unit & tahun |

### Views

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/formasi_jabatan/index.blade.php` | **Warna Sisa:** Menampilkan warna berbeda untuk sisa formasi (minus=merah bold, nol=kuning bold, >0=normal)<br>**Filter Unit Kerja Dinamis:** Dropdown unit kerja berubah sesuai kota/kabupaten yang dipilih<br>**Tombol Edit/Hapus per Unit:** Tombol aksi langsung di setiap card unit kerja<br>**Skip _meta key:** Melewati key '_meta' saat looping untuk mencegah error |
| `resources/views/sdm/index.blade.php` | **Filter Dropdown:** Menambahkan filter status formasi (Semua/Terpenuhi/Di Luar Formasi)<br>**Kolom Status Formasi:** Menampilkan badge status (hijau=terpenuhi, merah=di_luar_formasi)<br>**JavaScript:** Handler untuk filter status formasi |
| `resources/views/layouts/component/alert.blade.php` | Menambahkan alert warning untuk menampilkan peringatan over kuota |

---

## 3. Alur Logika Status Formasi

### Logic Recalculate Status Formasi

**Aturan Prioritas:**
1. SDM diurutkan berdasarkan `created_at ASC` (yang pertama masuk = prioritas tertinggi)
2. SDM ke-1 sampai ke-K (K = kuota) → status `'terpenuhi'`
3. SDM ke-(K+1) sampai selanjutnya → status `'di_luar_formasi'`

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

## 4. Perubahan yang Dilakukan (Versi 1.3.0 - Update)

### 4.1 Filter Kota/Kabupaten untuk Unit Kerja (Dinamis)

**Masalah:** Dropdown unit kerja menampilkan semua unit kerja meskipun sudah memfilter kota/kabupaten.

**Solusi:** Menambahkan filter dinamis pada dropdown unit kerja berdasarkan kota/kabupaten yang dipilih.

**File yang diubah:** `resources/views/formasi_jabatan/index.blade.php`

**Perubahan:**
- Menambahkan `id="unitFilter"` pada dropdown unit kerja
- Menambahkan `data-regency` attribute pada setiap option unit kerja
- Menambahkan fungsi JavaScript `filterUnitsByRegency()` untuk memfilter unit kerja secara real-time

**Hasil:** Ketika user memilih kota/kabupaten, dropdown unit kerja otomatis hanya menampilkan unit kerja di kota tersebut.

---

### 4.2 Tombol Edit Selalu Muncul di Halaman Formasi

**Masalah:** Tombol "Edit Grup" hanya muncul ketika user memilih filter Unit Kerja dan Tahun.

**Solusi:** Mengubah tombol menjadi button dengan JavaScript validation, sehingga selalu muncul namun memvalidasi filter saat diklik.

**Hasil:**
- Tombol Edit selalu visible
- Saat diklik tanpa filter → muncul alert
- Saat diklik dengan filter → langsung ke halaman edit

---

### 4.3 Tombol Edit & Hapus per Unit Kerja

**Masalah:** User harus menggunakan filter untuk mengedit formasi unit kerja tertentu.

**Solusi:** Menambahkan tombol Edit dan Hapus langsung di setiap card/tabel unit kerja.

**File yang diubah:**
1. `app/Http/Controllers/FormasiJabatanController.php`
2. `resources/views/formasi_jabatan/index.blade.php`
3. `routes/web.php`

**Fitur:**
- Tombol Edit → langsung ke halaman edit untuk unit & tahun tersebut
- Tombol Hapus → konfirmasi → hapus semua formasi di unit & tahun tersebut
- Layout lebih bersih dengan tombol di sebelah kanan tabel

---

### 4.4 Perbaikan Error "undefined array key kuota"

**Masalah:** Setelah menambahkan metadata `_meta` ke struktur `$table`, terjadi error "undefined array key kuota" saat looping.

**Solusi:** Melewati (skip) key `_meta` saat looping di view dengan `@if($key === '_meta') @continue @endif`

---

## 5. Use Cases

### Use Case 1: Tambah Pegawai Saat Kuota Penuh

**Kondisi Awal:**
- Formasi: Pengawas Keselamatan Pelayaran
- Unit Kerja: Kantor KSOP Tanjung Priok
- Kuota: 5
- Terisi: 5
- Sisa: 0

**Aksi:** User tambah pegawai baru "Fajar"

**Proses:**
1. Sistem cek sisa = 5 - 5 = 0
2. Karena sisa <= 0, set `status_formasi = 'di_luar_formasi'`
3. Tampilkan warning message
4. Simpan pegawai
5. Recalculate untuk SEMUA pegawai di formasi ini

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

**Aksi:** User hapus "Eka" (urutan ke-5)

**Proses:**
1. Hapus Eka
2. Recalculate untuk formasi ini

**Hasil:**
- Eka dihapus
- Fajar berubah dari "Di Luar Formasi" → "Terpenuhi"
- Tabel formasi menunjukkan sisa = 0 (kuning bold)

---

### Use Case 3: Pindah Pegawai ke Formasi Lain

**Kondisi Awal:**
- Formasi A: Kuota 3, Terisi 3 (Penuh)
- Formasi B: Kuota 5, Terisi 2 (Masih ada 3 kuota)

**Aksi:** User edit "Doni" dari Formasi A → Formasi B

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

## 6. Tampilan UI

### Warna Sisa di Formasi
- **Sisa < 0** (over kuota): **Merah Bold** (`text-danger fw-bold`)
- **Sisa = 0** (penuh): **Kuning Bold** (`text-warning fw-bold`)
- **Sisa > 0** (ada kuota): Normal

### Badge Status Formasi di Pegawai
- **Terpenuhi**: Green badge (`bg-success`)
- **Di Luar Formasi**: Red badge (`bg-danger`)
- **Tanpa Formasi**: Gray dash (`text-muted`)

---

## 7. Testing Checklist

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

## 8. Troubleshooting

### Masalah 1: Warning Message Tidak Muncul
**Solusi:** Cek file `layouts/component/alert.blade.php`, pastikan ada bagian `@if(session('warning'))`

### Masalah 2: Status Formasi Tidak Berubah
**Solusi:** Pastikan method `recalculateStatusFormasi()` dipanggil di `destroy()`, cek log Laravel

### Masalah 3: Sisa Tetap Tidak Bisa Minus
**Solusi:** Cek model `Formasijabatan.php`, pastikan `getSisaAttribute()` TIDAK menggunakan `max(0, ...)`

---

## 9. Catatan Penting

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

## 10. Future Enhancements

1. Export Excel dengan Status Formasi
2. Notifikasi Email jika ada formasi over kuota
3. Dashboard Widget untuk menampilkan jumlah pegawai di luar formasi
4. Audit Trail untuk tracking perubahan status_formasi

---

## Versi 1.4.0 - Modul Uji Kompetensi
**Tanggal:** 12 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi modul Uji Kompetensi baru untuk mengelola permohonan uji kompetensi JFT secara terintegrasi dengan data pegawai yang sudah ada. Modul ini mencakup sistem permohonan dengan workflow status (draft → diajukan → diverifikasi → terjadwal → selesai_uji → hasil_diinput → selesai), manajemen peserta batch, dan generate Berita Acara (Verifikasi & Hasil) dengan DomPDF.

---

## 1. File yang Dibuat (Baru)

### Database Migrations

| File Path | Deskripsi |
|-----------|-----------|
| `database/migrations/2026_03_12_create_ujikom_tables.php` | Migration untuk 3 tabel: ujikom_permohonan, ujikom_peserta, ujikom_berita_acara dengan soft deletes |

### Models

| File Path | Deskripsi |
|-----------|-----------|
| `app/Models/UjikomPermohonan.php` | Model permohonan dengan accessor status label, method auto-generate nomor, scope filters |
| `app/Models/UjikomPeserta.php` | Model peserta dengan relasi ke permohonan dan pegawai |
| `app/Models/UjikomBeritaAcara.php` | Model berita acara dengan relasi ke permohonan dan user |

### Controllers

| File Path | Deskripsi |
|-----------|-----------|
| `app/Http/Controllers/UjikomController.php` | Controller dengan 17 methods: index, create, store, show, edit, update, destroy, ajukan, verifikasi, tolak, inputJadwal, simpanJadwal, konfirmasiSelesai, inputHasil, simpanHasil, generateBA, exportPdf, getPegawaiList |

### Views

| File Path | Deskripsi |
|-----------|-----------|
| `resources/views/ujikom/index.blade.php` | Daftar permohonan dengan DataTables, filter status/unit kerja/tahun, badge warna |
| `resources/views/ujikom/create.blade.php` | Form tambah permohonan dengan Select2 AJAX untuk pegawai, dynamic rows |
| `resources/views/ujikom/edit.blade.php` | Form edit permohonan (hanya status draft) |
| `resources/views/ujikom/show.blade.php` | Detail permohonan dengan timeline stepper, tombol aksi per status, modal verifikasi/tolak |
| `resources/views/ujikom/jadwal.blade.php` | Form input jadwal & tempat pelaksanaan |
| `resources/views/ujikom/hasil.blade.php` | Form input hasil per peserta dengan dropdown Lulus/Tidak Lulus |
| `resources/views/ujikom/pdf/detail.blade.php` | PDF template untuk export detail permohonan |
| `resources/views/ujikom/pdf/berita_acara_verifikasi.blade.php` | PDF template Berita Acara Verifikasi dengan kop surat |
| `resources/views/ujikom/pdf/berita_acara_hasil.blade.php` | PDF template Berita Acara Hasil dengan tabel hasil dan coloring |

### Helpers

| File Path | Deskripsi |
|-----------|-----------|
| `app/helpers.php` | Helper functions: toRoman(), formatNomorPermohonanUjikom() |

---

## 2. File yang Dimodifikasi

### Composer

| File Path | Perubahan |
|-----------|-----------|
| `composer.json` | Menambahkan `"files": ["app/helpers.php"]` ke autoload untuk load helper functions |

### Routes

| File Path | Perubahan |
|-----------|-----------|
| `routes/web.php` | **Import:** Menambahkan `use App\Http\Controllers\UjikomController;`<br>**Route Group:** Menambahkan 18 routes untuk modul ujikom dengan prefix `/ujikom` dan permission middleware |

### Seeders

| File Path | Perubahan |
|-----------|-----------|
| `database/seeders/PermissionSeeder.php` | **Update:** Mengubah `create()` → `firstOrCreate()` untuk prevent duplicate<br>**Tambah:** 6 permissions baru (view ujikom, create ujikom, edit ujikom, delete ujikom, verifikasi ujikom, input hasil ujikom) |
| `database/seeders/RoleSeeder.php` | **Update:** Mengubah `Role::create()` → `Role::firstOrCreate()`<br>**Update:** Mengubah `givePermissionTo()` → `syncPermissions()`<br>**Tambah:** Mapping permissions ujikom ke role (super_admin: semua, admin: semua kecuali manage users, operator: view & create, viewer: view only) |

### Layouts

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/layouts/users/master.blade.php` | Menambahkan menu "Uji Kompetensi" di sidebar (setelah menu Pegawai JFT) dengan icon `fas fa-clipboard-check`, visible untuk role operator, admin, super_admin |

---

## 12. Changelog Summary

| Versi | Tanggal | Deskripsi |
|-------|---------|-----------|
| 1.4.1 | 12 Mar 2026 | Bug Fix: Filter pegawai, Generate BA, Edit page, Duplicate entry |
| 1.4.0 | 12 Mar 2026 | Modul Uji Kompetensi |
| 1.3.0 | 11 Mar 2026 | Laporan Terpadu (PAUSED - Error belum teridentifikasi) |
| 1.2.0 | 10 Mar 2026 | Implementasi Status Formasi (Over Kuota Diizinkan) |
| 1.1.0 | 10 Mar 2026 | Implementasi Spatie Laravel Permission |
| 1.0.0 | - | Versi awal dengan role sederhana (admin/user) |

---

**Dokumentasi ini dibuat pada:** 10 Maret 2026
**Versi Dokumentasi:** 1.4.0
**Update Terakhir:** 12 Maret 2026
**Penulis:** Claude Code (AI Assistant)

---

*End of CHANGELOG*

## Versi 1.3.0 - Laporan Terpadu
**Tanggal:** 11 Maret 2026
**Status:** ⚠️ DEVELOPMENT PAUSED (Ada error belum teridentifikasi)

---

## Ringkasan

Halaman Laporan Terpadu yang dapat diakses oleh role `admin` dan `super_admin` dengan 4 tab: Dashboard, Unit Kerja, Formasi, dan Pegawai JFT. Setiap tab memiliki filter parameter, tabel preview data, dan tombol export PDF & Excel.

---

## 1. File yang Dibuat (Baru)

### Controllers

| File Path | Deskripsi |
|-----------|-----------|
| `app/Http/Controllers/LaporanController.php` | Controller utama dengan methods: index(), exportPdf(), exportExcel(), getDashboardData(), getUnitKerjaData(), getFormasiData(), getPegawaiData(), dll. |

### Views

| File Path | Deskripsi |
|-----------|-----------|
| `resources/views/laporan/index.blade.php` | Halaman utama dengan 4 tabs, filter forms, tabel preview dengan DataTables, tombol export, Chart.js integration |
| `resources/views/laporan/pdf/dashboard.blade.php` | PDF template untuk dashboard report dengan summary cards + table |
| `resources/views/laporan/pdf/unit_kerja.blade.php` | PDF template untuk unit kerja report |
| `resources/views/laporan/pdf/formasi.blade.php` | PDF template untuk formasi report (landscape, 29 kolom) |
| `resources/views/laporan/pdf/pegawai.blade.php` | PDF template untuk pegawai report dengan status badges |

### Exports

| File Path | Deskripsi |
|-----------|-----------|
| `app/Exports/LaporanExcelExport.php` | Excel export dengan multiple sheets. Sheet classes: DashboardSheet, UnitKerjaSheet, FormasiSheet, PegawaiSheet |

---

## 2. File yang Dimodifikasi

### Routes

| File Path | Perubahan |
|-----------|-----------|
| `routes/web.php` | - Menambahkan import `LaporanController`<br>- Menambahkan route group untuk laporan dengan middleware role:admin|super_admin |

### Views - Layout

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/layouts/users/master.blade.php` | Menambahkan menu "Laporan" di sidebar (setelah menu Pegawai JFT), hanya visible untuk admin & super_admin |

---

## 3. Routes

```php
// Laporan Terpadu (hanya admin & super_admin)
Route::middleware(['role:admin|super_admin'])->prefix('laporan')->as('laporan.')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::get('export-pdf/{tab}', [LaporanController::class, 'exportPdf'])->name('export-pdf');
    Route::get('export-excel/{tab}', [LaporanController::class, 'exportExcel'])->name('export-excel');
});
```

---

## 4. Spesifikasi Fitur per Tab

### Tab 1: Dashboard

**Filters:**
- Tahun (dropdown)
- Provinsi (dropdown)
- Kabupaten/Kota (dropdown, dependent)

**Summary Cards:**
- Total Unit Kerja
- Total Formasi (Kuota)
- Total Terisi
- Total Sisa
- Total Pegawai
- Total Pegawai Di Luar Formasi

**Charts:**
- Bar Chart: Kuota vs Terisi per Provinsi
- Pie Chart: Distribusi Pegawai per Jenjang

**Table:** Ringkasan per Provinsi (No, Provinsi, Total Unit Kerja, Total Kuota, Total Terisi, Total Sisa, Total Pegawai)

**Export:**
- PDF: Landscape dengan summary cards + table (tanpa charts)
- Excel: 3 sheets (Summary statistics, Jenjang distribution, Province summary)

---

### Tab 2: Unit Kerja

**Filters:**
- Provinsi (dropdown)
- Kabupaten/Kota (dropdown)
- Jenis UPT (dropdown: Darat/Laut/Udara/Kereta)

**Table Columns:** No, Nama Unit Kerja, Jenis UPT, Provinsi, Kab/Kota, Jumlah Jabatan Formasi, Jumlah Pegawai

**Export:** PDF & Excel dengan table data sesuai filter

---

### Tab 3: Formasi

**Filters:**
- Tahun (dropdown)
- Provinsi (dropdown)
- Kabupaten/Kota (dropdown)
- Unit Kerja (dropdown, dependent)
- Jabatan (dropdown)

**Table Columns:** No, Unit Kerja, Nama Jabatan, Tahun, Kuota (9 jenjang + TOTAL), Terisi (9 jenjang + TOTAL), Sisa (9 jenjang + TOTAL)

**Styling:**
- Sisa < 0: **Bold Merah**
- Sisa = 0: **Bold Kuning**
- Sisa > 0: Normal

**Jenjang Order:** Pemula, Terampil, Mahir, Penyelia, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama

**Export:**
- PDF: Landscape (table sangat lebar: 29 kolom)
- Excel: Bold headers, auto-width columns, borders

---

### Tab 4: Pegawai JFT

**Filters:**
- Tahun (dropdown)
- Unit Kerja (dropdown)
- Jabatan (dropdown)
- Jenjang (dropdown)
- Status Formasi (dropdown: Semua/Terpenuhi/Di Luar Formasi)

**Table Columns:** No, Nama Pegawai, NIP, Jabatan, Jenjang, Unit Kerja, Provinsi, Kab/Kota, TMT Jabatan, Status Formasi

**Status Badges:**
- `terpenuhi` → Green badge "Terpenuhi"
- `di_luar_formasi` → Red badge "Di Luar Formasi"
- Others → Gray "-"

**Export:**
- PDF: Badges di-convert ke colored spans
- Excel: Status formasi as plain text

---

## 5. Error yang Diperbaiki

| No | Error | Fix |
|----|-------|-----|
| 1 | Target class [LaporanController] does not exist | Added `use App\Http\Controllers\LaporanController;` ke routes/web.php |
| 2 | Call to undefined method App\Models\Rumahsakit::formasi() | Changed `withCount('formasi')` → `withCount('formasis')` (plural) |
| 3 | Syntax error di laporan/index.blade.php line 80 | Fixed route parameter syntax |
| 4 | Tab buttons tidak clickable | Changed Bootstrap 5 syntax to Bootstrap 4 (`data-bs-toggle` → `data-toggle`) |
| 5 | Export PDF HTTP 500 - Memory Exhausted | Increased memory to 512M, optimized eager loading depth |
| 6 | Kop surat tidak muncul di PDF | Changed `public_path()` to `asset()` for web URL |
| 7 | Too few arguments to function exportPdf() | Fixed route parameter syntax untuk export buttons |

---

## 6. Masalah Belum Terselesaikan

⚠️ **ERROR TIDAK TERIDENTIFIKASI** - User menyebutkan "masih ada error" tapi tidak memberikan detail error.

**Kemungkinan penyebab:**
1. Error di salah satu export function
2. Error di query data untuk tab tertentu
3. Error di Chart.js rendering
4. Error di Excel export

**Langkah debugging saat resume:**
1. Cek Laravel logs: `storage/logs/laravel.log`
2. Test setiap tab satu per satu
3. Test export PDF & Excel untuk setiap tab
4. Cek browser console untuk JavaScript errors

---

## 7. Technical Notes

### Dependencies
- **DomPDF** - PDF generation
- **Maatwebsite Excel** - Excel export
- **Spatie Laravel Permission** - Role-based access control
- **Chart.js** - Dashboard charts
- **DataTables** - Table interactivity
- **AdminLTE 3** - UI template (Bootstrap 4, NOT Bootstrap 5)

### Memory Management
```php
// Di exportPdf() method
ini_set('memory_limit', '512M');
set_time_limit(300);
```

### Query Optimization
```php
// Limit eager loading depth untuk prevent memory issues
->with([
    'formasi:id,nama_formasi,unit_kerja_id,tahun_formasi',
    'formasi.jenjang:id,nama_jenjang',
    'formasi.unitkerja:no_rs,nama_rumahsakit,regency_id',
    'formasi.unitkerja.regency:id,name,type,province_id',
])
// Province loaded manually to avoid deep eager load
```

### ⚠️ Bootstrap Version Notice
**IMPORTANT:** AdminLTE uses **Bootstrap 4**, not Bootstrap 5!

**Correct syntax:**
```blade
<!-- Tabs -->
<button data-toggle="tab" data-target="#dashboard">

<!-- Modals -->
<button data-toggle="modal" data-target="#myModal">

<!-- Dropdowns -->
<button data-toggle="dropdown">
```

**NOT:**
```blade
<!-- WRONG for Bootstrap 5 -->
<button data-bs-toggle="tab" data-bs-target="#dashboard">
```

---

## 8. Testing Checklist (Saat Resume)

### Basic Functionality
- [ ] Halaman laporan accessible untuk admin & super_admin
- [ ] Halaman laporan NOT accessible untuk operator & viewer
- [ ] Menu Laporan muncul di sidebar untuk authorized users
- [ ] Navigation antar tab berfungsi

### Tab 1 - Dashboard
- [ ] Filter berfungsi (Tahun, Provinsi, Kab/Kota)
- [ ] Summary cards menampilkan data correct
- [ ] Bar chart muncul dengan data correct
- [ ] Pie chart muncul dengan data correct
- [ ] Table province summary menampilkan data correct
- [ ] Export PDF berfungsi
- [ ] Export Excel berfungsi dengan 3 sheets

### Tab 2 - Unit Kerja
- [ ] Filter berfungsi (Provinsi, Kab/Kota, Jenis UPT)
- [ ] Table menampilkan data unit kerja dengan jumlah formasi & pegawai
- [ ] Export PDF berfungsi
- [ ] Export Excel berfungsi

### Tab 3 - Formasi
- [ ] Filter berfungsi (Tahun, Provinsi, Kab/Kota, Unit Kerja, Jabatan)
- [ ] Table menampilkan kuota, terisi, sisa per jenjang
- [ ] Coloring untuk Sisa < 0 dan Sisa = 0 berfungsi
- [ ] Export PDF landscape berfungsi
- [ ] Export Excel berfungsi

### Tab 4 - Pegawai JFT
- [ ] Filter berfungsi (Tahun, Unit Kerja, Jabatan, Jenjang, Status Formasi)
- [ ] Table menampilkan data pegawai dengan status badge correct
- [ ] Export PDF berfungsi
- [ ] Export Excel berfungsi dengan status as plain text

### General
- [ ] Kop surat muncul di semua PDF
- [ ] Filter parameters muncul di semua PDF
- [ ] Tanggal cetak muncul di semua PDF
- [ ] No memory error saat export large dataset

---

## 9. Known Limitations

1. **Performance:** Large datasets may still cause memory issues despite optimization
2. **Charts:** Charts not included in PDF export (only tables)
3. **DataTables:** Client-side processing, may be slow for very large datasets
4. **No Pagination:** Tables show all data, may be slow for thousands of records

---

## 10. Quick Access untuk Debugging

**URL Langsung ke Tab:**
- Dashboard: `/user/laporan`
- Unit Kerja: `/user/laporan#unit-kerja`
- Formasi: `/user/laporan#formasi`
- Pegawai: `/user/laporan#pegawai`

**URL Export dengan Filter Contoh:**
```
/user/laporan/export-pdf/dashboard?province_id=11&regency_id=1101
/user/laporan/export-excel/unit_kerja?matra=Darat
/user/laporan/export-pdf/formasi?tahun=2024&province_id=11
/user/laporan/export-excel/pegawai?status_formasi=di_luar_formasi
```

**Artisan Commands:**
```bash
# Clear cache
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Check routes
php artisan route:list | grep laporan

# Check logs
tail -f storage/logs/laravel.log
```

---

## 11. Next Steps (Saat Resume)

1. **Identify Error** - Check Laravel logs, reproduce error, document error message
2. **Fix Identified Error** - Apply fix sesuai nature of error, test thoroughly
3. **Complete Testing** - Go through Testing Checklist, fix any issues found
4. **Performance Optimization (Optional)** - Implement server-side DataTables, add pagination, implement chunking untuk exports
5. **User Acceptance Testing** - Demo ke user, gather feedback, make adjustments

---

## 12. Changelog Summary

| Versi | Tanggal | Deskripsi |
|-------|---------|-----------|
| 1.3.0 | 11 Mar 2026 | Laporan Terpadu (PAUSED - Error belum teridentifikasi) |
| 1.2.0 | 10 Mar 2026 | Implementasi Status Formasi (Over Kuota Diizinkan) |
| 1.1.0 | 10 Mar 2026 | Implementasi Spatie Laravel Permission |
| 1.0.0 | - | Versi awal dengan role sederhana (admin/user) |

---

**Dokumentasi ini dibuat pada:** 10 Maret 2026
**Versi Dokumentasi:** 1.3.0
**Update Terakhir:** 11 Maret 2026
**Penulis:** Claude Code (AI Assistant)

---

*End of CHANGELOG*

---

## Versi 1.4.0 - Modul Uji Kompetensi
**Tanggal:** 12 Maret 2026
**Status:** Selesai ✅

---

## Ringkasan

Implementasi modul Uji Kompetensi baru untuk mengelola permohonan uji kompetensi JFT secara terintegrasi dengan data pegawai yang sudah ada. Modul ini mencakup sistem permohonan dengan workflow status (draft → diajukan → diverifikasi → terjadwal → selesai_uji → hasil_diinput → selesai), manajemen peserta batch, dan generate Berita Acara (Verifikasi & Hasil) dengan DomPDF.

---

## 1. File yang Dibuat (Baru)

### Database Migrations

| File Path | Deskripsi |
|-----------|-----------|
| `database/migrations/2026_03_12_create_ujikom_tables.php` | Migration untuk 3 tabel: ujikom_permohonan, ujikom_peserta, ujikom_berita_acara dengan soft deletes |

### Models

| File Path | Deskripsi |
|-----------|-----------|
| `app/Models/UjikomPermohonan.php` | Model permohonan dengan accessor status label, method auto-generate nomor, scope filters |
| `app/Models/UjikomPeserta.php` | Model peserta dengan relasi ke permohonan dan pegawai |
| `app/Models/UjikomBeritaAcara.php` | Model berita acara dengan relasi ke permohonan dan user |

### Controllers

| File Path | Deskripsi |
|-----------|-----------|
| `app/Http/Controllers/UjikomController.php` | Controller dengan 17 methods: index, create, store, show, edit, update, destroy, ajukan, verifikasi, tolak, inputJadwal, simpanJadwal, konfirmasiSelesai, inputHasil, simpanHasil, generateBA, exportPdf, getPegawaiList |

### Views

| File Path | Deskripsi |
|-----------|-----------|
| `resources/views/ujikom/index.blade.php` | Daftar permohonan dengan DataTables, filter status/unit kerja/tahun, badge warna |
| `resources/views/ujikom/create.blade.php` | Form tambah permohonan dengan Select2 AJAX untuk pegawai, dynamic rows |
| `resources/views/ujikom/edit.blade.php` | Form edit permohonan (hanya status draft) |
| `resources/views/ujikom/show.blade.php` | Detail permohonan dengan timeline stepper, tombol aksi per status, modal verifikasi/tolak |
| `resources/views/ujikom/jadwal.blade.php` | Form input jadwal & tempat pelaksanaan |
| `resources/views/ujikom/hasil.blade.php` | Form input hasil per peserta dengan dropdown Lulus/Tidak Lulus |
| `resources/views/ujikom/pdf/detail.blade.php` | PDF template untuk export detail permohonan |
| `resources/views/ujikom/pdf/berita_acara_verifikasi.blade.php` | PDF template Berita Acara Verifikasi dengan kop surat |
| `resources/views/ujikom/pdf/berita_acara_hasil.blade.php` | PDF template Berita Acara Hasil dengan tabel hasil dan coloring |

### Helpers

| File Path | Deskripsi |
|-----------|-----------|
| `app/helpers.php` | Helper functions: toRoman(), formatNomorPermohonanUjikom() |

---

## 2. File yang Dimodifikasi

### Composer

| File Path | Perubahan |
|-----------|-----------|
| `composer.json` | Menambahkan `"files": ["app/helpers.php"]` ke autoload untuk load helper functions |

### Routes

| File Path | Perubahan |
|-----------|-----------|
| `routes/web.php` | **Import:** Menambahkan `use App\Http\Controllers\UjikomController;`<br>**Route Group:** Menambahkan 18 routes untuk modul ujikom dengan prefix `/ujikom` dan permission middleware |

### Seeders

| File Path | Perubahan |
|-----------|-----------|
| `database/seeders/PermissionSeeder.php` | **Update:** Mengubah `create()` → `firstOrCreate()` untuk prevent duplicate<br>**Tambah:** 6 permissions baru (view ujikom, create ujikom, edit ujikom, delete ujikom, verifikasi ujikom, input hasil ujikom) |
| `database/seeders/RoleSeeder.php` | **Update:** Mengubah `Role::create()` → `Role::firstOrCreate()`<br>**Update:** Mengubah `givePermissionTo()` → `syncPermissions()`<br>**Tambah:** Mapping permissions ujikom ke role (super_admin: semua, admin: semua kecuali manage users, operator: view & create, viewer: view only) |

### Layouts

| File Path | Perubahan |
|-----------|-----------|
| `resources/views/layouts/users/master.blade.php` | Menambahkan menu "Uji Kompetensi" di sidebar (setelah menu Pegawai JFT) dengan icon `fas fa-clipboard-check`, visible untuk role operator, admin, super_admin |

---

## 3. Struktur Role & Permission (Update)

### Role yang Diimplementasikan

| Role | Akses Uji Kompetensi |
|------|---------------------|
| **super_admin** | Full access: view, create, edit, delete, verifikasi, input hasil |
| **admin** | Semua fitur kecuali manage users |
| **operator** | View & Create saja (tidak bisa edit/delete/verifikasi/input hasil) |
| **viewer** | View only (tidak bisa akses menu ujikom) |

### Permissions Baru

| Permission | Deskripsi |
|------------|-----------|
| `view ujikom` | Melihat daftar dan detail permohonan |
| `create ujikom` | Membuat permohonan baru & menambah peserta |
| `edit ujikom` | Mengedit permohonan (hanya status draft) |
| `delete ujikom` | Menghapus permohonan (hanya status draft) |
| `verifikasi ujikom` | Verifikasi, tolak, input jadwal, konfirmasi selesai |
| `input hasil ujikom` | Input hasil uji kompetensi per peserta |

---

## 4. Alur Workflow Status

```
draft → diajukan → diverifikasi → terjadwal → selesai_uji → hasil_diinput → selesai
  ↑                                                                              ↓
  └──────────────────────────── tolak (dengan catatan) ──────────────────────────┘
```

| Status | Deskripsi | Aksi Tersedia |
|--------|-----------|---------------|
| **draft** | Permohonan baru, belum diajukan | Edit, Delete, Ajukan |
| **diajukan** | Menunggu verifikasi admin | Verifikasi, Tolak |
| **diverifikasi** | Berkas sudah verified, menunggu jadwal | Input Jadwal |
| **terjadwal** | Jadwal sudah ditentukan | Konfirmasi Selesai Uji |
| **selesai_uji** | Uji sudah dilaksanakan | Input Hasil |
| **hasil_diinput** | Hasil sudah diinput | Generate BA Hasil |
| **selesai** | BA Hasil sudah dibuat | Download BA |

---

## 5. Format Nomor Permohonan

**Format:** `UJIKOM/[ROMAWI-BULAN]/[TAHUN]/[NO-URUT]`

**Contoh:** `UJIKOM/III/2026/001`

**Logic Generate:**
1. Get current month & year dari tanggal permohonan
2. Convert month ke roman numeral (I-XII)
3. Count existing permohonan in same month/year
4. Increment dan pad dengan zeros (3 digits)

**Helper Function:** `formatNomorPermohonanUjikom($noUrut, $tanggal)`

---

## 6. Database Structure

### Table: ujikom_permohonan

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary Key |
| nomor_permohonan | VARCHAR(50) | Unique, Auto-generate |
| unit_kerja_id | BIGINT UNSIGNED | FK → rumahsakits.no_rs |
| file_surat_permohonan | VARCHAR(255) | Path file PDF upload |
| tanggal_permohonan | DATE | Tanggal permohonan |
| status | ENUM | draft, diajukan, diverifikasi, terjadwal, selesai_uji, hasil_diinput, selesai |
| catatan_verifikator | TEXT | Nullable, untuk catatan verifikasi/penolakan |
| tanggal_jadwal | DATE | Nullable |
| tempat_ujikom | VARCHAR(255) | Nullable |
| created_by | BIGINT UNSIGNED | FK → users.id |
| timestamps | TIMESTAMP | created_at, updated_at |
| deleted_at | TIMESTAMP | Soft deletes |

### Table: ujikom_peserta

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary Key |
| ujikom_permohonan_id | BIGINT UNSIGNED | FK → ujikom_permohonan.id (cascade) |
| pegawai_id | BIGINT UNSIGNED | FK → sumber_daya_manusia.id |
| hasil | ENUM | belum, lulus, tidak_lulus (default: belum) |
| catatan_hasil | TEXT | Nullable |
| timestamps | TIMESTAMP | created_at, updated_at |
| deleted_at | TIMESTAMP | Soft deletes |

**Unique Constraint:** `(ujikom_permohonan_id, pegawai_id)` - Satu pegawai hanya sekali per permohonan

### Table: ujikom_berita_acara

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary Key |
| ujikom_permohonan_id | BIGINT UNSIGNED | FK → ujikom_permohonan.id (cascade) |
| jenis | ENUM | verifikasi, hasil |
| file_path | VARCHAR(255) | Path file PDF berita acara |
| dibuat_oleh | BIGINT UNSIGNED | FK → users.id |
| tanggal_dibuat | TIMESTAMP | Auto current timestamp |
| timestamps | TIMESTAMP | created_at, updated_at |
| deleted_at | TIMESTAMP | Soft deletes |

**Unique Constraint:** `(ujikom_permohonan_id, jenis)` - Satu jenis BA per permohonan

---

## 7. Routes

**Base URL:** `/ujikom`
**Route Name Prefix:** `ujikom.`
**Middleware:** `auth` + `permission:...`

| Method | URL | Name | Permission | Description |
|--------|-----|------|------------|-------------|
| GET | `/ujikom` | ujikom.index | view ujikom | Daftar permohonan |
| GET | `/ujikom/create` | ujikom.create | create ujikom | Form tambah permohonan |
| POST | `/ujikom` | ujikom.store | create ujikom | Simpan permohonan baru |
| GET | `/ujikom/{id}` | ujikom.show | view ujikom | Detail permohonan |
| GET | `/ujikom/{id}/edit` | ujikom.edit | edit ujikom | Form edit permohonan |
| PUT | `/ujikom/{id}` | ujikom.update | edit ujikom | Update permohonan |
| DELETE | `/ujikom/{id}` | ujikom.destroy | delete ujikom | Hapus permohonan |
| POST | `/ujikom/{id}/ajukan` | ujikom.ajukan | create ujikom | Submit draft → diajukan |
| POST | `/ujikom/{id}/verifikasi` | ujikom.verifikasi | verifikasi ujikom | Verify → diverifikasi |
| POST | `/ujikom/{id}/tolak` | ujikom.tolak | verifikasi ujikom | Reject → draft |
| GET | `/ujikom/{id}/jadwal` | ujikom.jadwal | verifikasi ujikom | Form input jadwal |
| POST | `/ujikom/{id}/jadwal` | ujikom.simpan-jadwal | verifikasi ujikom | Simpan jadwal → terjadwal |
| POST | `/ujikom/{id}/konfirmasi` | ujikom.konfirmasi | verifikasi ujikom | Confirm → selesai_uji |
| GET | `/ujikom/{id}/hasil` | ujikom.hasil | input hasil ujikom | Form input hasil |
| POST | `/ujikom/{id}/hasil` | ujikom.simpan-hasil | input hasil ujikom | Simpan hasil → hasil_diinput |
| GET | `/ujikom/{id}/ba/{jenis}` | ujikom.ba | verifikasi ujikom | Generate BA PDF |
| GET | `/ujikom/{id}/export` | ujikom.export | view ujikom | Export detail PDF |
| GET | `/ujikom/pegawai-list` | ujikom.pegawai-list | view ujikom | AJAX endpoint Select2 |

---

## 8. Key Features

### 1. Nomor Permohonan Auto-Generate
- Format terstruktur dengan romawi bulan
- Auto-increment per bulan/tahun
- Helper function reusable

### 2. Workflow Status dengan Badge Warna
- Draft: Abu-abu (bg-secondary)
- Diajukan: Biru (bg-primary)
- Diverifikasi: Kuning (bg-warning)
- Terjadwal: Ungu/info (bg-info)
- Selesai Uji: Oranye (bg-orange custom)
- Hasil Diinput: Teal (bg-teal custom)
- Selesai: Hijau (bg-success)

### 3. Timeline Stepper
- Visual progress bar dengan icons
- Completed: Check circle (green)
- Active: Solid circle (blue)
- Pending: Outline circle (gray)

### 4. File Upload Management
- Storage: `storage/app/public/ujikom/surat_permohonan/` dan `ujikom/berita_acara/`
- Validation: PDF only, max 2MB
- Auto-delete old file saat update
- Download link dengan `asset('storage/...')`

### 5. Select2 AJAX untuk Pegawai
- Endpoint: `GET /ujikom/pegawai-list?q={query}&unit_kerja_id={id}`
- Response: JSON dengan `{id, text, nama, nip, jabatan, jenjang}`
- Filter by unit kerja
- Minimum input: 2 characters
- Limit: 20 results

### 6. Dynamic Rows untuk Peserta
- Tambah/hapus peserta secara dinamis
- Validasi duplikasi
- Auto-update hidden inputs untuk form submission
- Warning jika ganti unit kerja saat ada peserta

### 7. PDF Generation dengan DomPDF
- Kop surat dengan `{{ asset('images/kop_surat.png') }}`
- Paper A4 portrait
- Table dengan borders
- Coloring untuk hasil (lulus: hijau muda, tidak lulus: merah muda)
- Auto-save ke storage dan create record di ujikom_berita_acara

### 8. Role-Based Access Control
- @can directive di views
- Permission middleware di routes
- Operator hanya create, tidak edit/delete/verify
- Admin & Super Admin full access
- Viewer tidak bisa akses menu

---

## 9. Testing Checklist

### Basic Functionality
- [ ] Login sebagai operator - Bisa create permohonan draft
- [ ] Login sebagai admin - Bisa verifikasi, input jadwal, input hasil
- [ ] Login sebagai viewer - Tidak bisa akses menu ujikom
- [ ] Login sebagai super_admin - Full access

### Workflow Testing
- [ ] Create draft permohonan dengan peserta → Simpan Draft
- [ ] Create draft permohonan → Simpan & Ajukan → Status berubah ke diajukan
- [ ] Draft → Edit → Update berhasil
- [ ] Draft → Hapus → Permohonan terhapus
- [ ] Diajukan → Verifikasi → Status diverifikasi + catatan tersimpan
- [ ] Diajukan → Tolak → Status kembali ke draft + catatan penolakan
- [ ] Diverifikasi → Input Jadwal → Status terjadwal + BA Verifikasi dibuat
- [ ] Terjadwal → Konfirmasi Selesai → Status selesai_uji
- [ ] Selesai Uji → Input Hasil → Status hasil_diinput
- [ ] Hasil Diinput → Generate BA Hasil → Status selesai + BA downloaded

### PDF Testing
- [ ] Generate BA Verifikasi - PDF terdownload dengan kop surat & tabel peserta
- [ ] Generate BA Hasil - PDF terdownload dengan tabel hasil & coloring
- [ ] Export Detail - PDF terdownload dengan info lengkap permohonan
- [ ] Verify kop_surat.png appears correctly

### Filter & Search
- [ ] Filter by status works
- [ ] Filter by unit kerja works
- [ ] Filter by tahun works
- [ ] DataTables search works
- [ ] Reset filter works

### AJAX Testing
- [ ] Select2 pegawai search works (min 2 chars)
- [ ] Pegawai list filtered by unit kerja
- [ ] Duplicate peserta validation works
- [ ] Dynamic rows add/remove works

### File Upload Testing
- [ ] Upload surat permohonan (PDF) - Success
- [ ] Upload non-PDF - Validation error
- [ ] Upload > 2MB - Validation error
- [ ] Download file yang diupload - Works
- [ ] Update file → Old file deleted

---

## 10. Known Issues & Workarounds

### None saat ini

Semua fitur telah diimplementasi sesuai spesifikasi dan berfungsi dengan baik.

---

## 11. Next Steps (Future Enhancements)

1. **Notifikasi Email** - Kirim email ke unit kerja saat status berubah
2. **Reminder System** - Notifikasi admin jika ada permohonan yang menunggu verifikasi > 3 hari
3. **Batch Verification** - Verifikasi beberapa permohonan sekaligus
4. **Export Excel** - Export daftar permohonan ke Excel
5. **Sertifikat Digital** - Generate sertifikat untuk peserta yang lulus
6. **Dashboard Widget** - Widget di dashboard untuk statistik uji kompetensi
7. **Audit Trail** - Log semua perubahan status dengan user & timestamp
8. **Attachment Tambahan** - Upload dokumen pendukung lain (foto kegiatan, dll)

---

## 12. Changelog Summary

| Versi | Tanggal | Deskripsi |
|-------|---------|-----------|
| 1.4.1 | 12 Mar 2026 | Bug Fix: Filter pegawai, Generate BA, Edit page, Duplicate entry |
| 1.4.0 | 12 Mar 2026 | Modul Uji Kompetensi |
| 1.3.0 | 11 Mar 2026 | Laporan Terpadu (PAUSED - Error belum teridentifikasi) |
| 1.2.0 | 10 Mar 2026 | Implementasi Status Formasi (Over Kuota Diizinkan) |
| 1.1.0 | 10 Mar 2026 | Implementasi Spatie Laravel Permission |
| 1.0.0 | - | Versi awal dengan role sederhana (admin/user) |

---

**Dokumentasi ini dibuat pada:** 10 Maret 2026
**Versi Dokumentasi:** 1.4.0
**Update Terakhir:** 12 Maret 2026
**Penulis:** Claude Code (AI Assistant)

---
