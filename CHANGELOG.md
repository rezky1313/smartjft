# CHANGELOG - SMART JFT

## Catatan Konvensi Git
- **Jangan** tambahkan `Co-Authored-By: Claude` di commit message (per instruksi user, 24 Juni 2026)

---

## Versi 1.32.0 - Fix: /user/sdm Server-Side DataTables (8,5MB → 20KB)
**Tanggal:** 6 Agustus 2026 (browser interaktif ditest 9 Agustus 2026)
**Status:** ✅ Diverifikasi HTTP nyata (payload sebelum/sesudah, filter, search, tombol Aksi) DAN ditest browser interaktif (Chrome, super_admin) — search "JOSE" (1 hasil), filter dropdown "Di Luar Formasi" (2 hasil, cocok persis dgn recordsFiltered HTTP), sorting kolom NIP (server-side, urutan berubah benar), tombol Hapus (dialog SweetAlert2 "Hapus Data Pegawai?" muncul, dibatalkan lewat "Batal", tidak ada baris terhapus), tombol Karir & Diklat (mengarah ke pegawai yang benar, dicek by nama & NIP di halaman tujuan). Nol error console di semua interaksi.

### Ringkasan

Ditemukan saat kerja PKR-02: `/user/sdm` (halaman Pegawai JFT, PALING SERING diakses admin) memuat ~8,5MB HTML client-side untuk ~3.940 baris — lebih besar dari masalah PKR-01 (3,99MB) yang sudah diperbaiki. Pre-existing dari sebelum PKR-01, bukan regresi. Dikonversi ke server-side DataTables mengikuti pola persis `/user/pkr` (PKR-01) dan `/karir/diklat` (PKR-02).

### A. Diagnostik

- **Kolom**: 14 kolom (No, NIP, Nama, JK, Status, Pangkat/Gol, Jenjang via join formasi.jenjang, Unit Kerja via formasi.unitKerja diutamakan lalu fallback unit_kerja_id langsung, Provinsi via unit.regency.province, TMT, Masa Jabatan [accessor PHP, bukan kolom DB], Status Formasi, Aktif, Aksi [Karir/Diklat/Edit/Hapus]). Filter: cuma 1 (Status Formasi). Semua dipertahankan persis.
- **Fitur bulk**: TIDAK ADA — dicek langsung di blade, nol checkbox/select-all/bulk-action. Import Excel & Download Template adalah navigasi terpisah yang tidak bergantung pada state DOM tabel — tidak terpengaruh konversi. Task 3 (redesain bulk) tidak berlaku.
- **Index DB**: `nip`, `unit_kerja_id`, `formasi_jabatan_id`, `jenjang_kode` semua sudah terindex. `nama_lengkap`/`status_formasi` tidak, konsisten dengan trade-off yang sudah diterima di PKR-01/02.

### B. File Diubah

- `app/Http/Controllers/SdmController.php` — `index()` jadi shell-only; tambah `data()` (endpoint AJAX server-side DataTables, join formasi/jenjang/unit_kerja/regency/province dengan `whereNull('deleted_at')` eksplisit di setiap join ke tabel ber-SoftDeletes — gotcha yang sama dengan PKR-03).
- `routes/web.php` — `user.sdm.data` (GET `/user/sdm/data`), didaftarkan SEBELUM `Route::resource('sdm', ...)` supaya literal `sdm/data` tidak ketangkap wildcard `sdm/{sdm}` (show) — pola sama dengan `karir/diklat`.
- `resources/views/sdm/index.blade.php` — dikonversi ke `serverSide: true`; tombol Hapus diganti dari native `confirm()` ke SweetAlert2 (`konfirmasiHapusSdm()`) — perbaikan yang dilakukan sekalian karena view ini sudah disentuh, sesuai aturan kerja "SweetAlert2 untuk konfirmasi".

### C. Hasil Verifikasi (request HTTP nyata, data produksi penuh)

```
GET /user/sdm (shell)                              -> 310ms,  20 KB   (dulu ~8,5 MB)
GET /user/sdm/data (tanpa filter, page 1)           -> 126ms,  30 KB   (recordsTotal=3940)
GET /user/sdm/data?filter_status=terpenuhi          -> 117ms,  30 KB   (recordsFiltered=3938, match persis query manual)
GET /user/sdm/data?search[value]=JOSE               -> 36ms,   1,3 KB  (recordsFiltered=1)
```

Tombol Aksi dikonfirmasi mengarah ke pegawai yang tepat: link "Karir" (`/user/pkr/{id}`) dan "Diklat" (`/karir/diklat/riwayat/{id}`) dicek langsung di HTML respons, ID cocok dengan baris yang bersangkutan. Import Excel & Download Template dicek masih HTTP 200 (tidak terpengaruh, sesuai dugaan diagnostik).

### D. Fitur Bulk

Tidak ditemukan — lihat diagnostik A. Tidak ada yang perlu didesain ulang.

### E. Bug DI LUAR SCOPE ditemukan (dilaporkan, TIDAK diperbaiki)

`/user/sdm/create` dan `/user/sdm/{id}/edit` (form tambah/edit pegawai, `sdm/form.blade.php`) memuat **502KB, ~4,4 detik** — dropdown Formasi men-dump semua opsi dikelompokkan per unit kerja sekaligus, kelas masalah yang SAMA dengan yang diperbaiki di PKR-02 (dropdown pegawai). Ini SUDAH ADA sebelum sesi ini (dikonfirmasi via `git log` — file tidak disentuh sesi manapun sejak v1.26.0), bukan bagian dari scope task ini (yang khusus `/user/sdm` index). Kandidat kuat untuk fix serupa (Select2 AJAX) kalau diminta terpisah.

---

## Versi 1.31.0 - PKR-02: Riwayat Diklat (Modul Baru)
**Tanggal:** 6 Agustus 2026
**Status:** ✅ Unit-scoping Admin Unit DIDEMOKAN nyata (bukan diasumsikan) dgn 2 identitas admin_unit berbeda unit, 403 terverifikasi dua arah + endpoint data()/pegawai-options dikonfirmasi tidak bocor lintas unit. CRUD end-to-end (create/edit/update/destroy + upload/hapus file fisik) diverifikasi transaksi-rollback. **Belum ditest browser interaktif.**

### Ringkasan

Modul baru: riwayat diklat pegawai, dengan Admin Unit boleh input/edit/hapus data diklat pegawai DI UNITNYA SENDIRI (bukan cuma Admin Pusbin) — sesuai keputusan desain eksplisit di prompt. Data pendukung keputusan pengembangan karir, bukan prasyarat PKR-01/PKR-03 (keduanya sudah jalan tanpa modul ini).

### A. Diagnostik

- **Pola unit-scoping Admin Unit** direuse persis dari `UjikomPendaftaranController::index()`: `$user->hasRole('admin_unit')` → `where('unit_kerja_id', $user->unit_kerja_id)`. Kuncinya kolom **`users.unit_kerja_id`** (langsung di tabel users, dikonfirmasi via `Schema::getColumnListing`).
- **Pola upload berkas**: `UjikomPendaftaranController` pakai `$file->store('ujikom/berkas', 'public')` tapi validasinya longgar (cuma `isValid()`). Validasi lebih ketat direuse dari `PengangkatanController`/`UjikomJadwalController` (`mimes:...|max:5120`) — sertifikat diklat pakai `mimes:pdf,jpg,jpeg,png|max:5120`, disimpan `diklat/sertifikat` disk `public`.
- **Route placeholder ditemukan & direuse**: `karir.diklat.index` (sebelumnya `coming_soon`) — **BUKAN** `user.diklat.*` seperti disebut prompt, supaya konsisten dgn `karir.analitik.index` (PKR-03) dan tidak bikin menu ganda di sidebar.
- **Soft delete**: dicek 4 tabel detail/transaksional sejenis (`ujikom_pendaftaran_berkas`, `pengangkatan_surat`, `pkr_angka_kredit_riwayat`, `ujikom_hasil`) — semua TIDAK pakai soft delete. `pegawai_diklat` mengikuti pola yang sama (tanpa soft delete).
- Kolom `unit_kerja_id` di `sumber_daya_manusia` dikonfirmasi ulang (sudah dipakai sejak PKR-01/03).
- Tidak ada halaman `show` profil pegawai yang berfungsi (route ada, method controller tidak ada — gap pre-existing dari diagnostik PKR-01) — Task 4c dilewati.

### B. Bug ditemukan & diperbaiki sendiri saat implementasi (bukan pre-existing)

- **Payload besar di form `create`**: dropdown pegawai awalnya dump SEMUA pegawai scoped ke `<select>` sekaligus — 630KB utk super_admin (≈3.940 opsi tanpa filter unit). Pelajaran sama dgn PKR-01: diganti Select2 AJAX search-as-you-type (endpoint baru `pegawaiOptions()`, scope unit dipaksa server-side dari role login, BUKAN dari parameter request — beda dari pola asli `UjikomPendaftaranController::getPegawai()` yang percaya `unit_kerja_id` kiriman client). Payload turun ke ~21KB.
- Pagination `->links()` di halaman "Belum Diklat" diarahkan eksplisit ke `pagination::bootstrap-4` (default Laravel pakai Tailwind, tidak konsisten dengan "Bootstrap 4 murni").

### C. File Baru

- `database/migrations/2026_08_06_000001_create_pegawai_diklat_table.php`, `app/Models/PegawaiDiklat.php`.
- `app/Http/Controllers/PegawaiDiklatController.php` — `index()`/`data()` (server-side DataTables SEJAK AWAL, agregat per-pegawai, batch-enrich hanya baris hasil paginasi), `riwayat($sdm)`, `create()`/`store()`, `edit()`/`update()`, `destroy()`, `rekapPerUnit()` (SQL agregat murni), `pegawaiBelumDiklat()` (`whereNotExists`, bukan loop PHP), `pegawaiOptions()` (AJAX Select2).
- `resources/views/diklat/{index,form,riwayat,rekap,belum}.blade.php`. `riwayat.blade.php` baru di luar daftar route eksplisit prompt — perlu utk Task 4a ("tombol Diklat... link ke riwayat diklat pegawai tersebut") sekaligus tempat CRUD individual per-pegawai (index agregat per-pegawai tidak cocok jadi target edit/hapus 1 entri).

### D. Diubah

- `routes/web.php` — group `karir/diklat` (baca: admin/super_admin/admin_unit/kabid/viewer; tulis: admin/super_admin/admin_unit — role gate di level route DITAMBAH scoping unit di level controller, dua lapis).
- `resources/views/layouts/users/master.blade.php` — sidebar "Riwayat Diklat" diaktifkan (sebelumnya placeholder "Segera").
- `resources/views/sdm/index.blade.php` — tombol "Diklat" di kolom Aksi, di sebelah "Karir". **Catatan**: `sdm.index` sendiri tidak di-scope per unit utk Admin Unit (pre-existing, di luar scope PKR-02) — tombol akan tetap muncul utk semua pegawai, tapi klik ke pegawai unit lain akan 403 server-side (bukan cuma disembunyikan).

### E. Verifikasi Unit-Scoping (WAJIB, didemokan nyata)

```
User asli id=8 (admin_unit, unit_kerja_id=1700):
  - Akses riwayat SDM #8301 (unit 1700, unitnya sendiri)  -> HTTP 200
  - Akses riwayat SDM #3385 (unit 1080, unit LAIN)         -> HTTP 403  [LULUS]
  - GET data() tanpa filter -> recordsTotal=3, cocok persis dgn query manual unit 1700

User simulasi B (dibuat via transaksi-rollback, unit_kerja_id=1080):
  - Akses riwayat SDM #3385 (unitnya sendiri)              -> HTTP 200
  - Akses riwayat SDM #8301 (unit 1700, unit LAIN)          -> HTTP 403  [LULUS]
  - edit() entri milik unit 1700                            -> HTTP 403  [LULUS]

pegawaiOptions() (AJAX dropdown) discoped ke admin_unit id=8: 2 hasil,
  keduanya SDM unit 1700 -- 0 bocor dari unit lain.
```

### F. Verifikasi CRUD End-to-End (transaksi rollback)

`store()` → file ter-upload ke `storage/app/public/diklat/sertifikat/`, record tersimpan benar. `update()` oleh pemilik unit yang sah → berhasil. `destroy()` → record DAN file fisik terhapus keduanya. Semua di-rollback bersih (`PegawaiDiklat::count()` = 0 setelah setiap uji).

### G. Waktu Respons (data produksi, request HTTP nyata)

```
GET /karir/diklat            -> 105ms,  76 KB
GET /karir/diklat/data       -> 34ms,   9.4 KB
GET /karir/diklat/rekap      -> 12ms,   17 KB
GET /karir/diklat/belum      -> 75ms,   97 KB
GET /karir/diklat/create     -> 565ms,  21 KB   (setelah fix Select2 AJAX; sempat 630 KB sebelum fix)
GET /karir/diklat/riwayat/x  -> 14-29ms, ~16-17 KB
```

**Observasi di luar scope (tidak diperbaiki)**: `/user/sdm` (listing Pegawai JFT) masih client-side DataTables, payload ~8,5MB — pre-existing dari sebelum PKR-01, bukan dibuat/diperparah oleh perubahan PKR-02 ini (cuma menambah 1 tombol kecil). Kalau mau diperbaiki dengan pola server-side yang sama seperti `/user/pkr` (PKR-01 Bagian 3), perlu task terpisah.

---

## Catatan Dokumentasi — 5 Agustus 2026

**Trim `CLAUDE.md`**: dipangkas dari 9.705 → 6.757 karakter (~2.900 karakter dihapus). Yang dihapus
murni konten yang bisa diturunkan langsung dari kode (bukan aturan/instruksi kerja): tabel Tech
Stack (framework/library — sudah ada di `composer.json`/`package.json`) dan tur fitur
Dashboard/Unit Kerja/Formasi/Pegawai JFT (daftar kolom, tombol, form — semua terbaca langsung dari
`resources/views/`). Gotcha non-obvious (penamaan `rumahsakit` = unit kerja, aturan matching Import
Pegawai) dan seluruh larangan/instruksi kerja di "Catatan Penting untuk Claude" tetap utuh, tidak
disentuh. Tujuannya mengurangi konteks yang dibaca ulang tiap sesi baru tanpa kehilangan informasi
yang penting. Riwayat lengkap ada di `git log -p -- CLAUDE.md`.

---

## Versi 1.30.0 - PKR-03: Analitik & Tren Pengembangan Karir JFT
**Tanggal:** 5 Agustus 2026
**Status:** ✅ Diverifikasi lewat request HTTP nyata dgn data produksi penuh (waktu respons + cross-check aritmatika manual). **Belum ditest browser interaktif.** Kemungkinan modul PKR terakhir yang direncanakan — Dokumen Acuan Kerja menyusul setelah ini.

### Ringkasan

Modul level agregat/nasional (beda dari PKR-01 yang per-individu): (1) tren jumlah pengangkatan per tahun, breakdown per jenjang & per moda transportasi; (2) analisis ketersediaan formasi nasional + drill-down per unit kerja. Controller baru `PkrAnalitikController` (terpisah dari `PkrController`) — semua angka murni hasil `GROUP BY`/aggregate SQL, PHP cuma merapikan format output, sesuai instruksi eksplisit prompt.

### A. Diagnostik & Koreksi Asumsi

- **Tidak ada field "tanggal SK Pengangkatan selesai"**. 3 kandidat dicek: `pengangkatan_permohonan.tanggal_disetujui` (ada di kolom tapi **tidak pernah ditulis kode manapun** — vestigial, cuma 1 baris terisi dari seed lama), `pengangkatan_surat` (tidak ada kolom tanggal, cuma flag `ditandatangani`), `sumber_daya_manusia.tmt_pengangkatan` (**aktif di-set** oleh `PengangkatanPermohonan::selesaikan()`, tapi baru 15/3.940 pegawai terisi). **Keputusan user**: pakai `tmt_pengangkatan` — semantik paling sesuai meski datanya masih sepi.
- **Field moda transportasi** = `unit_kerja.matra`, 4 nilai bersih: Darat/Laut/Udara/Kereta.
- **Route placeholder ditemukan & di-reuse**: `karir.analitik.index` (`/karir/analitik`), sebelumnya closure `coming_soon`, sekarang diarahkan ke `PkrAnalitikController@index` — nama route & middleware (`auth` saja) TIDAK diubah supaya sidebar tidak perlu disentuh dan akses tidak diam-diam dipersempit.
- **Chart Dashboard yang sudah ada** (`PetaDashboardController.php`, chart "Tren per Tahun") ternyata group by `formasi_jabatan.tahun_formasi` (tahun anggaran formasi), **bukan** tanggal pengangkatan — pola rendering Chart.js-nya diikuti (data `@json()` langsung ke Blade, bukan AJAX), tapi grouping tahunnya **tidak** disamakan karena beda makna dari yang diminta.
- **`formasi_final` dikonfirmasi ulang tidak ada** di `formasi_jabatan` (cuma `kuota`) — sama seperti temuan PKR-01 Bagian 2, dipakai `kuota - terisi`.
- **Tahun formasi default BUKAN `now()->year`**: `tahun_formasi=2026` cuma 4 baris/1 unit kerja (kuota 14) — hampir kosong; **2025** yang jadi tahun operatif nyata (1.136 baris, 455 unit kerja, kuota 5.740, 99,8% SDM aktif-berformasi terikat ke sini). Halaman default ke tahun dengan SDM terbanyak (dihitung dinamis via query, bukan hardcode), tahun tetap bisa dipilih manual lewat dropdown.

### B. Bug ditemukan & diperbaiki SEBELUM dilaporkan (bukan pre-existing, ditemukan saat menulis Task 2 sendiri)

Query agregat awal Task 2 (SUM kuota per unit+jenjang) sempat menghasilkan **5.818** padahal `Formasijabatan::sum('kuota')` Eloquent memberi **5.740** — selisih 78 ditelusuri sampai ketemu: `formasi_jabatan`/`sumber_daya_manusia`/`unit_kerja` semua pakai `SoftDeletes`, tapi query `DB::table()` (query builder mentah) **tidak otomatis exclude baris `deleted_at`** (beda dari Eloquent model yang punya global scope otomatis) — 16 baris formasi soft-deleted ikut kehitung. Diperbaiki dengan `whereNull('deleted_at')` eksplisit di **semua** query raw controller ini, lalu diverifikasi ulang match persis (5.740).

### C. File Baru

- `app/Http/Controllers/PkrAnalitikController.php` — `index()`, `trenPerTahun()`, `analisisFormasi()` + helper privat.
- `resources/views/pkr/analitik.blade.php` — Chart.js line toggle per-Jenjang/per-Moda (tanpa reload), filter tahun range, card ringkas nasional per jenjang, tabel unit kerja collapse Bootstrap 4 + search client-side.

### D. Diubah

- `routes/web.php` — `karir.analitik.index` diarahkan ke controller (bukan `coming_soon`).
- `resources/views/layouts/users/master.blade.php` — sidebar "Analitik Pengembangan" diaktifkan (sebelumnya placeholder "Segera").

### E. Hasil Verifikasi (request HTTP nyata, data produksi penuh, bukan browser)

```
GET /karir/analitik (default: tren 2020-2026, formasi tahun 2025)
  -> 108ms (setelah cold-start pertama ~5,5s krn compile Blade view sekali; normal),
     ~1.000.000 bytes (455 unit kerja x breakdown jenjang, semua tersembunyi via collapse)
GET /karir/analitik?tahun_formasi=2026
  -> 28ms, 26.795 bytes
GET /karir/analitik?dari=2022&sampai=2026
  -> 61ms, ~1.000.000 bytes
```

**Cross-check aritmatika** (bukan cuma "tidak error"): SUM tersedia nasional 2025 = 5.740 (match persis `Formasijabatan::sum('kuota')`); SUM terisi nasional = 3.636 vs 3.638 SDM aktif-berformasi-2025 (selisih 2 = SDM dengan `formasi_jabatan_id` dangling/soft-deleted, temuan lama PKR-01 Bagian 3, konsisten). `trenPerTahun()` per-jenjang & per-moda dicocokkan manual terhadap 15 baris `tmt_pengangkatan` satu-per-satu — match persis semua.

**Catatan payload**: default page ~1MB HTML (455 unit kerja, jauh lebih kecil dari masalah ~4MB PKR-01 Bagian 2 yang sudah diperbaiki, tapi tetap perlu diperhatikan pas browser-test — beri tahu kalau terasa berat, bisa dikonversi ke server-side seperti `/user/pkr`).

---

## Versi 1.29.0 - Fix: Sinkronisasi jenjang_kode Pasca Pengangkatan JFT + Audit Read-Only
**Tanggal:** 5 Agustus 2026
**Status:** ✅ Diverifikasi: (1) audit read-only benar-benar dijalankan atas data produksi (bukan simulasi) — 0 mismatch ditemukan; (2) fix wiring diverifikasi lewat transaksi-rollback (kolom jenjang_kode sengaja dirusak lalu dikonfirmasi `selesaikan()` memperbaikinya balik ke nilai accessor yang benar, lalu rollback bersih). **Belum ditest browser interaktif.**

### Koreksi laporan sebelumnya (v1.28.0)

Klaim di CHANGELOG v1.28.0 bahwa "`PengangkatanController` tidak pernah update `formasi_jabatan_id`" **SALAH** — waktu itu cuma grep controller, logic sesungguhnya ada di model `PengangkatanPermohonan::selesaikan()` yang **SUDAH** meng-update `formasi_jabatan_id` (dan `tmt_pengangkatan`) sejak lama. Gap sebenarnya jauh lebih sempit: cuma `syncJenjangKode()` (baru ada sejak v1.28.0, dibuat SETELAH `selesaikan()` ditulis) yang belum pernah dipanggil dari titik ini.

### A. Diagnostik

- **Hanya SATU titik** status Pengangkatan JFT berubah jadi "selesai": `PengangkatanController::konfirmasiTtd()` → `PengangkatanPermohonan::selesaikan()`. Tidak ada controller/route "pengangkatan_lama" yang hidup (view ada, tapi orphan — tidak ada route mengarah ke sana).
- `selesaikan()` pakai `foreach` + `->update()` PER INSTANCE Eloquent (bukan bulk `query builder ->update()`) — event Eloquent normal terpicu, TIDAK ada pola bypass seperti 4 blok di `FormasiJabatanController` (v1.28.0).
- Field formasi tujuan: `PengangkatanKandidat.jabatan_tujuan_id` → FK ke `formasi_jabatan.id`, difilter `status_kandidat = 'direkomendasikan'` (kandidat `antrian`/menunggu SENGAJA tidak diproses `selesaikan()`, jadi bukan mismatch kalau formasinya belum berubah).

### B. Task 1 — Fix Wiring

- **UBAH** `app/Models/PengangkatanPermohonan.php::selesaikan()` — tambah `$pegawai->syncJenjangKode();` tepat setelah `$pegawai->update([...])` di dalam loop.

### C. Task 2 — Audit Read-Only

- **BARU** `app/Console/Commands/AuditJenjangMismatch.php` (`php artisan pkr:audit-jenjang-mismatch`) — bandingkan `formasi_jabatan_id` SDM saat ini vs formasi tujuan dari kandidat `direkomendasikan` di permohonan `selesai` **TERBARU per pegawai** (kalau pegawai naik jenjang >1x, permohonan lama tidak dianggap mismatch). Output: tabel console + CSV di `storage/app/`. TIDAK mengubah data apapun.

### D. Hasil Audit (data produksi, 5 Agustus 2026)

```
Permohonan Pengangkatan JFT berstatus 'selesai': 1
Pegawai unik yang pernah diangkat: 7
0 mismatch ditemukan.
```

Seluruh 7 pegawai yang pernah diangkat lewat 1 permohonan "selesai" yang ada di produksi sudah punya `formasi_jabatan_id` yang cocok persis dengan formasi tujuan pengangkatannya — **tidak ada data historis yang perlu dikoreksi**. Backfill/koreksi TIDAK dijalankan (sesuai instruksi, dan juga tidak ada yang perlu dikoreksi).

---

## Versi 1.28.0 - PKR-01 Bagian 3: Server-Side DataTables (perbaikan performa /user/pkr)
**Tanggal:** 4 Agustus 2026
**Status:** ✅ Diverifikasi lewat request HTTP nyata (bukan cuma smoke test kernel) dengan pengukuran waktu respons & ukuran payload untuk semua kombinasi filter, plus verifikasi silang status_komposit filter vs `prediksiKenaikanJenjang()` langsung (skenario simulasi transaksi-rollback, match persis). **Masih perlu ditest interaktif di browser oleh user sebelum lanjut Bagian 4.**

### Ringkasan

Mengonversi `/user/pkr` dari client-side DataTables (load semua ~3.940 baris tiap page-load, payload ~4MB) jadi server-side DataTables AJAX. Payload halaman utama turun dari ~4MB jadi ~15KB per page-load tabel (index shell sendiri ~78KB, hanya chrome halaman).

### A. Diagnostik

- Index DB yang relevan (FK `sdm_id`/`peserta_id`, kolom filter `unit_kerja_id`/`formasi_jabatan_id`) **sudah ada semua** sejak awal — tidak perlu migration index tambahan di luar kolom baru `jenjang_kode`.
- Tabel-tabel terkait masih sangat kecil di produksi saat diagnostik: `ujikom_jadwal`=3 baris, `ujikom_hasil`=1 baris, `pkr_angka_kredit_riwayat`=0 baris.
- **Bug pre-existing ditemukan (DI LUAR SCOPE, TIDAK diperbaiki)**: alur "Pengangkatan JFT" yang sudah `selesai` **tidak pernah meng-update `formasi_jabatan_id`** pegawai di `sumber_daya_manusia` — jadi `jenjang_kode` (accessor maupun kolom fisik baru) tetap mencerminkan formasi lama untuk siapa pun yang naik jenjang lewat alur itu. `PromotionController.php` (modul "Promosi Jabatan") juga menyentuh `formasi_jabatan_id` tapi diabaikan sesuai CLAUDE.md.

### B. Task 1 — Materialize `jenjang_kode`

- **BARU** `database/migrations/2026_08_04_000005_add_jenjang_kode_to_sumber_daya_manusia.php` — kolom `jenjang_kode` (string, nullable, indexed) di `sumber_daya_manusia`.
- **BARU** `app/Console/Commands/SyncJenjangKode.php` (`php artisan pkr:sync-jenjang-kode`) — backfill, memanggil accessor `getJenjangKodeAttribute()` yang SAMA (tanpa duplikasi logic). Hasil: **3.644/3.940 terisi, 296 NULL** (294 pegawai memang tanpa `formasi_jabatan_id` + 2 baris dengan `formasi_jabatan_id` yang **dangling/tidak ada padanan di `formasi_jabatan`** — data kotor pre-existing, bukan bug baru).
- **UBAH** `app/Models/Sdmmodels.php` — tambah `syncJenjangKode()` (instance) & `syncJenjangKodeForIds()` (batch static). **PENTING**: karena nama kolom fisik SAMA dengan accessor, Eloquent tetap mendahulukan accessor — `$sdm->jenjang_kode` di PHP TIDAK PERNAH membaca kolom fisik (selalu live-compute dari relasi seperti sebelumnya). Kolom fisik hanya berguna lewat query builder mentah (`WHERE jenjang_kode = ...`). Sengaja tidak dimasukkan `$fillable`.
- **UBAH** 6 titik nyata di mana `formasi_jabatan_id` SDM berubah, ditambah pemanggilan sync (bukan observer — sebagian titik pakai bulk `query builder ->update()` yang TIDAK memicu event Eloquent sama sekali):
  `SdmController::store()`, `::update()`, loop Import Excel (3 sub-titik); `FormasiJabatanController` blok delete/reassign formasi tahun sama + `remapSdmToNewFormasi()` (4 blok bulk update, 2 di antaranya null-kan `jenjang_kode` langsung dalam update yang sama, 1 blok pakai `syncJenjangKodeForIds()` karena assignment ke formasi nyata); `RekomendasiFormasiController::simpanPegawaiDishub()`.

### C. Task 2 — Endpoint Server-Side `PkrController::data()`

- **BARU** route `user.pkr.data` (GET `/user/pkr/data`).
- Filter `search`/`unit_kerja_id`/`jenjang_kode` dieksekusi murni SQL (WHERE langsung, memakai kolom fisik baru).
- **Filter `status_komposit` TIDAK diimplementasi sebagai WHERE SQL murni** — dievaluasi, dilaporkan ke user, dan disepakati sebagai keputusan sadar (bukan dipaksakan): status komposit adalah ladder 4-kondisi berurutan-prioritas (AK/ujikom/predikat/formasi, salah satunya butuh suffix-mapping PHP-only untuk cocokkan nama JF+jenjang ke `formasi_jabatan`) — menerjemahkan ini jadi satu CASE WHEN SQL bersarang beresiko drift dari `tentukanStatusKomposit()`. Solusi dipakai: reuse `batchStatusKomposit()` **PERSIS SAMA** dengan Bagian 2 (bukan logic baru), dijalankan atas subset yang sudah dipersempit search/unit/jenjang oleh SQL, baru difilter+diurutkan+dipaginasi di PHP.
- Sorting native SQL untuk kolom Nama/NIP/Unit Kerja (via join `unit_kerja`)/Jenjang; kolom Prediksi Pangkat & Status Komposit non-orderable (dihitung per-baris hasil paginasi saja, sesuai instruksi).

### D. Task 3 — View `pkr/index.blade.php`

- Diganti total ke `serverSide: true`, filter dropdown trigger `ajax.reload()` (bukan reload halaman/GET form lagi).
- Checkbox working-list pakai event delegation (baris diganti tiap draw AJAX) + teks eksplisit "centang hanya berlaku untuk baris yang sedang tampil di halaman ini" (paginasi server-side, TIDAK ada fitur "pilih semua yang cocok filter").

### E. Task 4 — Verifikasi Fitur Lain Tidak Rusak

- `exportWorkingList()` dan `hitungLulusBelumDiangkat()` (alert 6-bulan) **tidak berubah sama sekali** — keduanya sudah independen dari pagination sejak Bagian 2 (ambil `sdm_ids` eksplisit / query agregat penuh), dikonfirmasi lewat code review, tidak butuh penyesuaian.

### F. Task 5 — Hasil Verifikasi (request HTTP nyata, BUKAN browser)

```
Tanpa filter (page 1):              200ms,  15.2 KB, recordsTotal=3940, recordsFiltered=3940
Filter unit_kerja_id:                50ms,  15.5 KB, recordsFiltered=31
Filter jenjang_kode=terampil:        62ms,  15.7 KB, recordsFiltered=1622
Filter status_komposit=AK Kurang:  3762ms,  15.2 KB, recordsFiltered=3245  <- jalur PHP batch-compute, LAMBAT
Search nama (1 match):               33ms,   0.7 KB, recordsFiltered=1
Index shell (bukan lagi 4MB):        278ms, 77.6 KB
```

**⚠️ Filter `status_komposit` TANPA filter unit/jenjang lain makan ~3,7 detik** (batch-compute atas hampir seluruh 3.940 baris) — jauh lebih lambat dari filter SQL murni lainnya. Ini konsekuensi sadar dari keputusan di atas (status komposit sengaja TIDAK di-cache karena volatil, beda dari `jenjang_kode` yang stabil). Perlu did-diskusikan apakah ini cukup dapat diterima atau butuh optimasi lanjutan (mis. materialized status dgn refresh berkala) di fase berikutnya.

Verifikasi silang: skenario simulasi (SDM Ahli Pertama asli, AK/ujikom-lulus/formasi disuntik via transaksi-rollback) — `prediksiKenaikanJenjang()` langsung mengembalikan **"Siap Diangkat"**, endpoint `/user/pkr/data?status_komposit=Siap Diangkat` mengembalikan **recordsFiltered=1** dengan SDM yang SAMA persis. Transaksi di-rollback bersih (dikonfirmasi 0 baris tersisa di `pkr_angka_kredit_riwayat`, `ujikom_pendaftaran` kode simulasi, dan `formasi_jabatan` id yang dibuat).

---

## Versi 1.27.0 - PKR-01 Bagian 2: Prediksi Pangkat/Jenjang, Status Komposit & Tabel Listing
**Tanggal:** 4 Agustus 2026
**Status:** ✅ Diverifikasi lewat script standalone (transaksi DB di-rollback utk skenario simulasi) + smoke test HTTP nyata (GET `/user/pkr`, `/user/pkr/{sdm}`, `/user/sdm` semua HTTP 200 tanpa exception). **Belum ditest interaktif di browser oleh user** (sesuai instruksi eksplisit: dilaporkan dulu sebelum lanjut Bagian 3).

### Ringkasan

Melengkapi PKR-01 dengan prediksi kenaikan pangkat (siklus reguler 4 tahun, estimasi dari NIP), prediksi kenaikan jenjang + status kesiapan komposit (AK/ujikom/predikat/formasi), dan halaman listing seluruh pegawai dengan working-list export. **Diagnostik awal menemukan beberapa asumsi prompt tidak cocok dengan struktur data riil** — dikoreksi setelah konfirmasi user, didokumentasikan detail di bawah dan di memory.

### A. Diagnostik & Koreksi Asumsi (dikonfirmasi user sebelum implementasi)

- **Tidak ada kolom "TMT Pangkat Terakhir"** — hanya `tmt_pengangkatan` (TMT masuk jenjang JFT, bukan TMT kenaikan pangkat/golongan), kosong di 3.925/3.940 (99,6%) data. **Keputusan user**: prediksi kenaikan pangkat SELALU pakai estimasi dari NIP, `tmt_pengangkatan` tidak dipakai sebagai proxy sama sekali (beda makna).
- **Tidak ada kolom golongan/ruang terpisah** — `pangkat_golongan` field teks bebas campuran ("Nama Pangkat (III/b)" ATAU golongan polos "III/b" ATAU sampah tanpa garis miring "VII"/"IX"/"IId"), kosong di 3.011/3.940 (76%). Dari 929 baris terisi, 624 (67%) berhasil diparse via regex, sisanya (termasuk 305 baris "V"/"VI"/"VII"/"IX"/"IId") sengaja di-treat sebagai tidak dapat ditentukan (bukan ditebak).
- **Format NIP pakai spasi** (`"19680905 198903 1 003"`, 21 karakter) — di-strip dulu sebelum ekstrak segmen TMT CPNS. 10/3.940 NIP dummy (12 digit) terdeteksi & ditandai `data_tidak_lengkap`. **699/3.930 (17,7%) NIP standar punya segmen "bulan" bernilai 21** (bukan 01-12, kemungkinan kode jalur rekrutmen khusus) — divalidasi & ditandai `data_tidak_lengkap`, bukan dipaksa jadi tanggal.
- **Kolom ledger AK** yang benar adalah `angka_kredit_diperoleh` (bukan `nilai_ak` seperti disebut prompt).
- **"formasi_final" tidak berlaku umum** — kolom itu spesifik modul RF-01 (usulan PKB), bukan kapasitas slot pegawai eksisting. Dipakai `Formasijabatan::sisa` (accessor yang sudah ada, sudah dipakai `PengangkatanController`) untuk cek `formasi_tersedia`.
- **Tidak ada pola server-side DataTables** di codebase manapun untuk diikuti (semua modul load-all + client-side). **Keputusan user**: tabel index PKR tetap client-side (konsisten modul lain), status komposit di-BATCH-compute (~6 query total, bukan query per-baris) supaya tetap wajar untuk ~3.940 pegawai.
- **Spec status komposit tidak lengkap** — 2 kombinasi kondisi tidak terdefinisi eksplisit di prompt (predikat gagal sendirian; AK & ujikom dua-duanya belum), diisi dengan status baru `Predikat Belum Terpenuhi` dan default `AK Kurang` — didokumentasikan di kode (`PkrController::tentukanStatusKomposit()`).

### B. File Baru

- `database/migrations/2026_08_04_000004_create_pkr_referensi_pangkat_table.php` — tabel `pkr_referensi_pangkat` (17 baris urutan pangkat I/a s.d. IV/e).
- `database/seeders/PkrReferensiPangkatSeeder.php`
- `app/Models/PkrReferensiPangkat.php` — `next()`, `cari()`, `normalisasiGolongan()`, cache statis per-request (tabel kecil, dipanggil ribuan kali di halaman index).
- `app/Exports/PkrWorkingListExport.php` — export Excel working list kenaikan pangkat.
- `resources/views/pkr/index.blade.php` — tabel listing seluruh pegawai aktif: filter Unit Kerja/Jenjang/Status Komposit, checkbox working-list (baris prediksi pangkat ≤3 bulan ditandai kuning), tombol export (SweetAlert2), alert lulus ujikom >6 bulan belum diangkat.

### C. Diubah

- `app/Http/Controllers/PkrController.php` — tambah `prediksiKenaikanPangkat()`, `prediksiKenaikanJenjang()`, `index()`, `exportWorkingList()`, plus helper privat (`ekstrakGolongan()`, `cekFormasiTersedia()`, `tentukanStatusKomposit()`, `batchStatusKomposit()`, `kodeDariNamaJenjang()`, `hitungLulusBelumDiangkat()`).
- `routes/web.php` — tambah `user.pkr.index` (GET), `user.pkr.working-list.export` (POST).
- `resources/views/layouts/users/master.blade.php` — aktifkan link sidebar "Tabel Pengembangan Karir" (sebelumnya placeholder "Segera") ke `user.pkr.index`.
- `resources/views/sdm/index.blade.php` — tambah tombol "Karir" (ikon id-card) di kolom Aksi, link ke `user.pkr.show`.

### D. Bug Pre-existing Ditemukan (DI LUAR SCOPE, TIDAK diperbaiki, dilaporkan sesuai instruksi)

- `app/Exports/UjikomHasilExcelExport.php` baris 50 pakai `$p->pegawai?->unitKerja?->nama_rs` — kolom `nama_rs` sudah tidak ada sejak rename v1.13.0 (kolom asli sekarang `nama_unit_kerja`). Kolom "Unit Kerja" di export itu kemungkinan selalu kosong sejak v1.13.0. Belum diperbaiki karena di luar scope PKR-01.

---

## Versi 1.26.0 - PKR-01 Bagian 1: Fondasi Database & Ledger Angka Kredit
**Tanggal:** 4 Agustus 2026
**Status:** ✅ Diverifikasi lewat script standalone (transaksi DB di-rollback) — kedua contoh perhitungan wajib match persis (Ahli Pertama/Baik/3 bulan = 3.125 AK; Mahir/Sangat Baik/12 bulan = 18.75 AK), termasuk uji end-to-end lewat `PkrController::storeAngkaKredit()` yang sesungguhnya (bukan cuma `hitungAK()` terisolasi). **Belum ditest di browser oleh user.**

### Ringkasan

Membangun fondasi modul baru **Pengembangan Karir JFT (PKR-01)** — command center data per pegawai untuk mencatat riwayat Angka Kredit (AK) periodik dan menghitung AK kumulatif terhadap ambang batas kenaikan jenjang, mengikuti PerBKN 3/2023. Bagian 1 ini fokus ke struktur database, model, dan form input riwayat AK — belum ke fitur prediksi kenaikan pangkat/jenjang otomatis (menyusul di bagian berikutnya).

### Perubahan

- **BARU** `database/migrations/2026_08_04_000003_create_pkr_tables.php` — 4 tabel: `pkr_referensi_koefisien` (koefisien AK tahunan per jenjang), `pkr_referensi_predikat` (persentase per predikat kinerja SKP), `pkr_ambang_batas_jenjang` (AK kumulatif minimal untuk naik jenjang, per kategori keterampilan/keahlian), `pkr_angka_kredit_riwayat` (ledger riwayat per pegawai — `persentase_predikat` & `koefisien_tahunan` disimpan **at time of entry**, bukan di-lookup ulang, supaya riwayat lama tidak berubah kalau tabel referensi diedit nanti).
- **BARU** `database/seeders/PkrReferensiSeeder.php` — seed 3 tabel referensi sesuai nilai resmi PerBKN 3/2023 (8 jenjang, 5 predikat, 6 ambang batas jenjang).
- **BARU** `app/Models/PkrAngkaKreditRiwayat.php` (relasi `sdm()`, `penilai()`, static `hitungAK($jumlahBulan, $persentase, $koefisien)`), `PkrReferensiKoefisien.php`, `PkrReferensiPredikat.php`, `PkrAmbangBatasJenjang.php`.
- **BARU** `app/Http/Controllers/PkrController.php` — `show()` (halaman command center per pegawai: ringkasan AK kumulatif + ambang batas + form input + riwayat), `hitungAkKumulatif()`, `ambangBatasNaikJenjang()`, `storeAngkaKredit()`.
- **BARU** `resources/views/pkr/show.blade.php` — form input riwayat AK dengan preview realtime (JS, tanpa AJAX — koefisien jenjang & persentase per predikat sudah tersedia di halaman lewat data attribute) + tabel riwayat.
- **BARU** routes `user.pkr.show` (GET `user/pkr/{sdm}`, role admin/super_admin/admin_unit/kabid_perencanaan_jft/viewer) dan `user.pkr.angka-kredit.store` (POST, role admin/super_admin/admin_unit — "atasan langsung").
- **UBAH** `app/Models/Sdmmodels.php` — tambah accessor `jenjang_kode` dan `jenjang_nama`. **Gotcha penting**: tabel `sumber_daya_manusia` **tidak punya kolom `jenjang` langsung** — harus diturunkan dari `formasi_jabatan_id` → `jenjang_jabatan.nama_jenjang` (format `"{Nama JF} {Jenjang}"`), dinormalisasi ke short-code (`ahli_pertama`, dst.) lewat pencocokan akhiran nama terhadap 8 nama jenjang resmi. Diverifikasi 100% cocok di seluruh 198 baris `jenjang_jabatan` (22 JF) sebelum dipakai di production code — pseudocode awal prompt (`$sdm->jenjang`) tidak match struktur data asli.

---

## Versi 1.25.0 - RF-1C: Verifikasi, Berita Acara (TTD Digital), & Surat Rekomendasi Formasi
**Tanggal:** 4 Agustus 2026
**Status:** ✅ Diverifikasi lewat Tinker — skenario penuh 1 usulan dari `verifikasi_disepakati` → TTD kedua pihak → `ba_selesai` → terbit surat → konfirmasi TTD final → `selesai`, dijalankan dalam satu transaksi DB dan di-rollback. Kuota Formasi per jenjang dikonfirmasi bertambah **match persis** sesuai `formasi_final` (Pemula +4, Terampil +22, Mahir +7, Penyelia +6). Juga diverifikasi terpisah: `kembalikanKeDraft()` (override Bagian 2), blokir TTD dobel, blokir TTD lintas-unit (403), blokir `updateHasilFinal()` setelah `ba_selesai`. **Belum ditest di browser oleh user.**

---

## Ringkasan

Melengkapi modul Rekomendasi Formasi (RF-01) dengan alur verifikasi offline (dicatat manual oleh Admin Pusbin, pertemuannya sendiri di luar sistem), Berita Acara dengan tanda tangan digital Opsi B (jejak audit, bukan TTE bersertifikat), dan penerbitan Surat Rekomendasi Formasi yang otomatis menambah kuota Formasi. Pola generate PDF & update Formasi mengikuti persis yang sudah terbukti jalan di modul Pengangkatan JFT (`generateSurat()`/`konfirmasiTtd()`), termasuk 1 penyimpangan disengaja dari pseudocode awal yang dijelaskan di Bagian C.

---

## Perubahan

### A. Struktur Database Baru

- **BARU** `database/migrations/2026_08_04_000001_create_rekomendasi_formasi_surat_table.php` — tabel `rekomendasi_formasi_surat` (mirroring `pengangkatan_surat`: `nomor_surat` nullable — TIDAK di-generate otomatis, diisi lewat proses persuratan institusi di luar sistem, sama seperti `pengangkatan_surat`; `tanggal_surat`; `ditandatangani`), unique per usulan.
- **BARU** `database/migrations/2026_08_04_000002_add_catatan_override_to_rekomendasi_formasi_usulan.php` — kolom `catatan_override` (text, nullable) di `rekomendasi_formasi_usulan`, audit trail sederhana untuk fitur "kembalikan ke Draft" (Bagian 2).
- **BARU** `app/Models/RekomendasiFormasiBeritaAcara.php`, `RekomendasiFormasiSurat.php` — model untuk 2 tabel di atas (tabel `rekomendasi_formasi_berita_acara` sendiri sudah ada sejak RF-1A tapi belum punya model Eloquent).
- **UBAH** `app/Models/RekomendasiFormasiUsulan.php` — tambah relasi `beritaAcara()` (hasOne), `surat()` (hasOne), `catatan_override` ke fillable.
- **UBAH** `app/helpers.php` — tambah `formatNomorBeritaAcaraRekomendasiFormasi()`, format `BA-RF/{romawi-bulan}/{tahun}/{no-urut}`, konsisten dengan `formatNomorPermohonanPengangkatan()` yang sudah ada.

### B. Verifikasi & Berita Acara TTD Digital

- **BARU** `RekomendasiFormasiController::tandaiVerifikasiDisepakati()` — `diajukan`/`menunggu_verifikasi` → `verifikasi_disepakati`, membuat record BA dengan nomor otomatis. Sistem HANYA mencatat hasil kesepakatan pertemuan (Zoom/tatap muka) yang terjadi di luar sistem — tidak menjadwalkan/memfasilitasi pertemuannya.
- **BARU** `tandaTanganBA()` — TTD digital Opsi B (jejak audit: user, waktu, IP — bukan TTE bersertifikat). Pihak Pusbin **hanya role `kabid_perencanaan_jft`** yang berwenang (SENGAJA tidak termasuk admin/super_admin — TTD ini merepresentasikan identitas personal Kepala Bidang, bukan hak administratif umum). Pihak pengusul: `admin_unit` dari unit kerja yang sama dengan usulan. Kedua pihak TTD → status otomatis `ba_selesai`.
- **BARU** `cetakBeritaAcara()` — PDF format Lampiran IV PM 4/2024 (2 pihak penandatangan), `resources/views/rekomendasi_formasi/pdf/berita_acara.blade.php`.
- **UBAH** `RekomendasiFormasiController::updateHasilFinal()` (RF-1B) — tambah blokir status: TIDAK bisa diedit lagi setelah `ba_selesai`/`menunggu_ttd_rekomendasi`/`selesai` (sebelumnya cuma dicek role, tanpa cek status).

### C. Surat Rekomendasi Formasi & Update Kuota Formasi

- **BARU** `terbitkanSuratRekomendasi()` — **menyimpang dari pseudocode prompt yang cuma `redirect()` tanpa PDF**, diimplementasikan mengikuti pola NYATA `PengangkatanController::generateSurat()`: 1 request GET sekaligus transisi status (`ba_selesai`→`menunggu_ttd_rekomendasi`) DAN download PDF, bisa diunduh ulang berkali-kali tanpa duplikasi status/record. Ini sesuai instruksi eksplisit prompt sendiri ("pola identik PengangkatanController@generateSurat, JANGAN dibuat beda pola tanpa alasan kuat") — pseudocode contoh yang diberikan tidak match pola aslinya, saya ikuti pola asli yang terbukti jalan.
- **BARU** `konfirmasiTtdRekomendasi()` — `menunggu_ttd_rekomendasi` → `selesai`. Menambahkan `formasi_final` ke kuota `formasi_jabatan` per jenjang lewat `firstOrCreate()` (kriteria: `unit_kerja_id` + `nama_formasi='Penguji Kendaraan Bermotor'` + `jenjang_id` dari `jenjang_jabatan` + `tahun_formasi=$usulan->tahun` — **PENTING**: `tahun_formasi` WAJIB masuk kriteria match karena `formasi_jabatan` menyimpan 1 baris per tahun per unit+jenjang, bukan 1 baris kumulatif) `->increment('kuota', ...)`. Jenjang dengan `formasi_final <= 0` dilewati (tidak bikin baris kosong).
- **BARU** `resources/views/rekomendasi_formasi/pdf/surat_rekomendasi.blade.php` — mirroring `pengangkatan/pdf/surat_rekomendasi.blade.php`, TTD Kapusbin JFT tetap manual/fisik (belum ada TTE resmi), nomor surat resmi TIDAK auto-generate (sama seperti Pengangkatan, diisi lewat proses persuratan institusi).

### D. Kunci Data & Override (Bagian 2)

- `edit()`/`update()` (RF-1B) — pengecekan status `in_array($usulan->status, ['draft', 'diajukan'])` **sudah otomatis memenuhi** requirement "kunci data setelah verifikasi_disepakati" tanpa perlu perubahan (status apapun setelah `diajukan` sudah terblokir). Pesan error diperjelas menyebut jalur override yang tersedia.
- **BARU** `kembalikanKeDraft()` — override eksplisit Admin Pusbin (`admin`/`super_admin` saja, TIDAK termasuk `kabid_perencanaan_jft`), WAJIB isi alasan (validasi min 5 karakter), dicatat ke `catatan_override` (append, bukan overwrite, supaya riwayat override sebelumnya tidak hilang kalau terjadi berkali-kali). Diblokir kalau status sudah `selesai` (kuota Formasi sudah bertambah sungguhan di titik itu, butuh penanganan manual terpisah — di luar scope).

### E. Timeline/Stepper & Route

- **UBAH** `resources/views/rekomendasi_formasi/show.blade.php` — stepper visual (pola CSS/HTML sama persis dengan `ujikom/show.blade.php`), 6 langkah: Draft → Diajukan → Verifikasi Disepakati → BA Ditandatangani (2 Pihak) → Menunggu TTD Rekomendasi → Selesai. Tombol "Tandai Verifikasi Telah Disepakati" & TTD digital pakai **SweetAlert2** (bukan `confirm()` native, sesuai instruksi eksplisit) — library sudah dimuat global di `layouts/users/master.blade.php` tapi RF-01 adalah pemakaian `Swal.fire()` PERTAMA di seluruh project (belum ada preseden lain untuk dicontoh). Tombol konfirmasi surat rekomendasi tetap pakai `confirm()` native, mengikuti pola asli `pengangkatan/show.blade.php` persis.
- **UBAH** `routes/web.php` — 6 route baru di dalam grup `user.` (`verifikasi-disepakati`, `tanda-tangan-ba`, `berita-acara` [GET, cetak PDF], `surat-rekomendasi` [GET, cetak PDF + transisi status], `konfirmasi-ttd-rekomendasi`, `kembalikan-draft`), masing-masing middleware role sesuai pihak yang berwenang.

---

## Catatan Teknis

- **Bug ditemukan & diperbaiki di skrip verifikasi saya sendiri (bukan di kode aplikasi):** percobaan pertama skenario penuh melaporkan kuota Formasi TIDAK bertambah — root cause-nya query verifikasi "sebelum/sesudah" tidak memfilter `tahun_formasi`, jadi membaca baris formasi tahun lain (data existing, tahun 2025) alih-alih baris baru yang dibuat `konfirmasiTtdRekomendasi()` untuk tahun 2027. Setelah skrip verifikasi diperbaiki, hasilnya match persis. Dicatat di sini karena polanya relevan: `formasi_jabatan` SELALU butuh `tahun_formasi` sebagai bagian kriteria pencarian/pembanding, tidak cukup unit+jenjang saja.
- Diverifikasi lewat PHP script standalone (bootstrap Laravel manual, BUKAN `php artisan tinker` — ditemukan `tinker --execute` dengan multi-line script kompleks berisi `try/catch`+`foreach` bersarang tidak reliable dieksekusi utuh lewat CLI argument maupun stdin pada sesi ini; script mandiri via `require bootstrap/app.php` jauh lebih stabil untuk skenario panjang bertahap seperti ini) — skenario lengkap 6 langkah + 4 pengecekan tambahan (TTD dobel, TTD lintas-unit, blokir `updateHasilFinal`, `kembalikanKeDraft` lengkap dengan validasi) semua dijalankan dalam SATU `DB::beginTransaction()`, di-`rollBack()` di akhir. Dikonfirmasi ulang: 0 usulan/BA/surat/formasi tahun-2027 tersisa, 0 user uji tersisa setelah rollback.
- **BELUM diuji:** interaksi sungguhan di browser (klik tombol SweetAlert2, isi form alasan kembalikan-draft, download PDF BA/surat rekomendasi sungguhan, tampilan stepper visual).

---

## Versi 1.24.0 - RF-1B: Form Usulan Rekomendasi Formasi & Mesin Kalkulasi Otomatis
**Tanggal:** 3-4 Agustus 2026
**Status:** ✅ Diverifikasi lewat Tinker — kalkulasi ΣWpv match PERSIS ke Excel referensi untuk seluruh 4 jenjang (data uji Kabupaten Bandung), pembulatan ROUNDUP-untuk-semua + fitur override manual `formasi_final` ditest (lihat Bagian D), full flow store()/show()/edit()/update()/updateHasilFinal() ditest untuk skenario Kemenhub & Dishub (termasuk upload pegawai, dijalankan dalam transaksi & di-rollback), otorisasi antar-unit & antar-role ditest. **Belum ditest di browser oleh user.**

---

## Ringkasan

Membangun alur pembuatan usulan Rekomendasi Formasi PKB: form 3-bagian (JF & unit kerja, variabel beban kerja, upload pegawai khusus Dishub), dan mesin kalkulasi otomatis Kebutuhan Formasi. **Sebelum bisa dipercaya, mesin kalkulasi divalidasi ketat terhadap file Excel referensi** — proses ini membongkar 5 bug tersembunyi di data `formula_rf_master` (hasil ekstraksi RF-1A) yang HARUS diperbaiki dulu, karena kalau tidak, hasil kalkulasi akan salah tanpa terlihat error apapun secara teknis.

---

## Perubahan

### A. Koreksi Menyeluruh Ekstraksi Formula (menggantikan pendekatan RF-1A-FIX yang ternyata masih keliru)

RF-1A memberi fallback `kb_diuji_total` ke semua baris tanpa formula Volume. Sesi sebelumnya (RF-1A-FIX) mencoba memperbaiki dengan aturan "per sub_unsur" (2 sub_unsur tertentu dijadikan NULL, sisanya tetap fallback) — **atas persetujuan Product Owner saat itu**. Saat RF-1B mencoba merekonstruksi angka ΣWpv Excel dari data itu, ditemukan pendekatan "per sub_unsur" itu sendiri salah: sifat "boleh berkontribusi atau tidak" adalah **properti per-baris** (ada/tidaknya formula di sel Volume baris itu sendiri), bukan properti kategori sub_unsur — dibuktikan lewat 2 baris kontra-contoh nyata (1 baris kategori "PENGUJIAN TIPE" yang justru PUNYA formula asli & seharusnya tetap kb_diuji_total; 1 baris kategori "ANALISIS DAN PENETAPAN HASIL" yang PALING TIDAK punya formula & seharusnya NULL).

- **UBAH** `app/Console/Commands/ImportFormulaRfPkb.php` — dirombak total:
  1. Hapus SEMUA fallback `kb_diuji_total` — sumber_volume murni hasil resolusi sel Volume baris itu sendiri, NULL kalau kosong (tanpa peduli sub_unsur).
  2. Tambah dukungan **baris agregat**: 2 baris di TERAMPIL_DISHUB ("Mengukur dimensi kendaraan bermotor, meliputi:" & "Memeriksa visual fisik kendaraan bermotor, meliputi:") punya kolom "jam" berisi `SUM(...)` yang mengagregasi child-nya, bukan sel waktu tunggal — sebelumnya baris ini ter-skip total dari ekstraksi (tidak ada equivalent di Pemula/Mahir/Penyelia).
  3. Waktu SELALU diambil dari **nilai terhitung kolom helper "jam"** (bukan dibaca mentah dari kolom waktu) — ditemukan 1 baris Terampil ("...alat uji Kebisingan") yang kolom waktu-nya menampilkan 1.5 tapi formula jam-nya di-hardcode `=1/60` (bukan mengikuti nilai 1.5) — inkonsistensi input manual di file sumber. Excel sendiri memakai hasil formula itu untuk kalkulasi Wpv, jadi sistem ikut itu juga.
  4. Nilai konstanta literal (`sumber_volume=konstanta_hari_kerja`) disimpan **apa adanya per baris**, tidak lagi diasumsikan selalu 240 — 1 baris Mahir ternyata literalnya 10, beda dari 14 baris lain yang literalnya 240.
- **BARU** `database/migrations/2026_08_10_000002_add_volume_konstanta_to_formula_rf_master.php` — kolom `volume_konstanta` (decimal, nullable) untuk mendukung poin 4 di atas.
- **UBAH** `app/Models/FormulaRfMaster.php` — tambah `volume_konstanta` ke fillable/casts.
- Command `formula-rf:import-pkb` dijalankan ulang (idempotent, replace `kode_jf=pkb`) — hasil: 118 baris (naik dari 116, +2 baris agregat Terampil).

**Hasil validasi ΣWpv (data Kabupaten Bandung, KBWU=43639, Uji Pertama=1073, Uji Reguler=33471, Numpang Masuk=2002, Mutasi Masuk=989, BBM Bensin=15491, BBM Solar=21919) — validasi tahap ini HANYA membuktikan rumus ΣWpv (Waktu×Volume) sudah benar, BUKAN aturan pembulatan (lihat Bagian C untuk pembulatan produksi):**

| Jenjang | ΣWpv Sistem | ΣWpv Excel (native) | Match? |
|---|---|---|---|
| Pemula | 5646.05 | 5646.05 | ✅ |
| Terampil | 26311.43 | 26311.43 | ✅ |
| Mahir | 7549.4167 | 7549.4167 | ✅ |
| Penyelia | 6255.8333 | 6255.8333 | ✅ |

Match persis untuk keempat jenjang. Angka ΣWpv Excel di atas diperoleh lewat `getCalculatedValue()` milik PhpSpreadsheet — engine parser+evaluator formula Excel independen (kode pihak ketiga, terpisah dari `hitungKebutuhanFormasi()`), BUKAN LibreOffice/Excel sungguhan (tidak terpasang di sistem ini) dan BUKAN dihitung manual oleh Claude. Kategorisasi per-baris (`sumber_volume`) sendiri tetap hasil interpretasi manual terhadap teks formula — divalidasi secara tidak langsung lewat kecocokan total 4 desimal di atas, bukan diverifikasi otomatis oleh engine.

**Klarifikasi penting (ditanyakan user 4 Agustus 2026, lihat Bagian C):** laporan awal RF-1B sempat mengubah kode produksi ke pembulatan CAMPURAN (ROUNDUP Pemula, ROUNDDOWN 3 jenjang lain) supaya `kebutuhan_bulat` sistem match 1:1 ke angka native Excel `Sheet1!V4:Y4` (5/21/6/5) — ini **menyimpang diam-diam dari keputusan eksplisit RF-1B ("ROUNDUP sesuai keputusan, berlaku SEMUA jenjang")** tanpa dikonfirmasi dulu ke user. Sudah dikembalikan ke ROUNDUP-untuk-semua, lihat Bagian C.

### B. Kolom `jenis_instansi` di Unit Kerja

- **BARU** `database/migrations/2026_08_10_000001_add_jenis_instansi_to_unit_kerja.php` — kolom `jenis_instansi` (enum kemenhub/dishub, nullable) + **backfill otomatis** untuk seluruh 539 baris `unit_kerja` yang sudah ada, dari kolom `instansi` yang sudah ada sebelumnya (`Daerah`→`dishub`, `Pusat`→`kemenhub`). Backfill ini deterministik, bukan tebakan — ditelusuri dulu: seluruh 136 baris `instansi='Daerah'` namanya diawali persis "Dinas Perhubungan ..." tanpa kecuali.
- **UBAH** `app/Models/UnitKerja.php` — tambah `jenis_instansi` ke fillable.

### C. Modul Rekomendasi Formasi — Controller, Route, View

- **BARU** `app/Http/Controllers/RekomendasiFormasiController.php` — `index/create/store/show/edit/update`. Mesin kalkulasi (`hitungKebutuhanFormasi()`) memakai `volume_konstanta` per baris (bukan hardcode 240).
- **BARU** `app/Models/RekomendasiFormasiUsulan.php`, `RekomendasiFormasiVariabel.php`, `RekomendasiFormasiHasil.php`, `RekomendasiFormasiPegawaiExisting.php`.
- **UBAH** `routes/web.php` — `Route::resource('rekomendasi-formasi', ...)` di dalam grup `user.` (route `user.rekomendasi-formasi.*`), `->only([...])` tanpa `destroy` (belum diminta/belum masuk akal untuk usulan yang sudah masuk alur verifikasi), middleware `role:admin_unit|admin|super_admin|kabid_perencanaan_jft|viewer` sesuai pembagian RF-1A.
- **UBAH** `resources/views/layouts/users/master.blade.php` — tambah menu sidebar "Rekomendasi Formasi", gated `@can('view rekomendasi formasi')`.
- **BARU** `resources/views/rekomendasi_formasi/{index,create,show,edit}.blade.php`.

**Bezetting Kemenhub** dihitung dari data Pegawai JFT existing yang tertaut `formasi_jabatan` JF "Penguji Kendaraan Bermotor" + jenjang terkait di unit kerja itu (bukan kolom "jenjang" langsung di `sumber_daya_manusia` — kolom itu tidak ada, jenjang selalu ditelusuri lewat `formasi_jabatan.jenjang_id` → `jenjang_jabatan.nama_jenjang`, formatnya gabungan "Nama JF + Jenjang").

**Data pegawai Dishub** yang diupload di Step 3 form disimpan permanen ke `sumber_daya_manusia` (real, `nama_lengkap` bukan `nama` — nama kolom sungguhan beda dari sketsa awal), ditautkan ke `formasi_jabatan` PKB unit itu KALAU sudah ada; kalau belum ada (Dishub yang belum tertib administrasi formasi), disimpan tanpa tautan formasi dengan `status_formasi='di_luar_formasi'` — TIDAK mengarang record formasi baru.

### D. Aturan Pembulatan — Dikembalikan ke ROUNDUP-untuk-Semua + Fitur Override Manual (4 Agustus 2026)

Menindaklanjuti klarifikasi user: laporan RF-1B awal sempat diam-diam mengubah kode produksi ke pembulatan campuran (ROUNDUP Pemula, ROUNDDOWN 3 jenjang lain) demi mengejar kecocokan validasi ke angka native Excel — ini bertentangan dengan keputusan eksplisit yang sudah tertulis di prompt RF-1B ("ROUNDUP sesuai keputusan, berlaku SEMUA jenjang") dan diterapkan tanpa konfirmasi dulu. Keputusan final user: kembali ke ROUNDUP-untuk-semua sebagai default sistem, DAN tambahkan fitur override manual per jenjang untuk mengantisipasi kalau pimpinan menghendaki ROUNDDOWN pada kasus tertentu.

- **UBAH** `app/Http/Controllers/RekomendasiFormasiController.php`:
  - `hitungKebutuhanFormasi()`: pembulatan dikembalikan ke `ceil()` (ROUNDUP) untuk keempat jenjang, tanpa pengecualian.
  - **BARU** `updateHasilFinal()` — override manual `formasi_final` per jenjang, khusus role Pusbin (`admin`, `super_admin`, `kabid_perencanaan_jft`; `admin_unit` diblokir eksplisit dengan 403 di level controller, bukan cuma middleware). `formasi_sistem` (hasil murni sistem, ROUNDUP) TIDAK PERNAH ikut berubah — tetap audit trail terpisah dari `formasi_final` (angka final yang dipakai/ditampilkan).
  - `update()` (edit variabel beban kerja oleh Admin Unit): diperbaiki supaya TIDAK diam-diam menimpa balik `formasi_final` yang sudah di-override manual Pusbin sebelumnya — override dipertahankan meski variabel diedit ulang & kalkulasi dijalankan ulang.
- **UBAH** `routes/web.php` — route baru `PUT rekomendasi-formasi/{id}/hasil-final` (`user.rekomendasi-formasi.hasil-final.update`), middleware `role:admin|super_admin|kabid_perencanaan_jft` (admin_unit tidak disertakan).
- **UBAH** `resources/views/rekomendasi_formasi/show.blade.php` — tabel hasil kalkulasi menampilkan kolom **Kebutuhan (Raw)** (angka sebelum dibulatkan, 4 desimal) supaya pimpinan bisa menimbang sendiri sebelum override; kolom **Formasi Final** jadi input editable khusus role Pusbin (form terpisah, submit ke route `hasil-final.update`), tetap read-only untuk role lain.

**Konsekuensi:** dengan ROUNDUP-untuk-semua, hasil sistem untuk data Kabupaten Bandung menjadi Pemula=5, Terampil=**22** (bukan 21 versi native Excel/ROUNDDOWN), Mahir=**7** (bukan 6), Penyelia=**6** (bukan 5) — sengaja berbeda dari angka native Excel untuk 3 jenjang tsb, sesuai keputusan produk yang berlaku sekarang.

---

## Catatan Teknis

- **Kenapa validasi ΣWpv tetap penting** meski keputusan pembulatan akhirnya beda dari Excel: rumus inti (Waktu × Volume, penjumlahan, sumber volume per baris) tetap harus benar dulu sebelum pembulatan apapun diterapkan — validasi ΣWpv membuktikan bagian ITU sudah benar, terlepas dari aturan pembulatan yang dipilih di atasnya.
- **Metodologi validasi ΣWpv (diklarifikasi 4 Agustus 2026):** angka "Excel native" dipakai sebagai pembanding diperoleh lewat `PhpSpreadsheet::getCalculatedValue()` — sebuah *engine* parser+evaluator formula Excel pihak ketiga yang independen dari kode `hitungKebutuhanFormasi()`, BUKAN dengan membuka file di LibreOffice/Excel sungguhan (tidak terpasang di sistem ini) dan BUKAN dihitung manual oleh Claude memakai pemahaman yang sama dengan implementasi PHP-nya. Namun kategorisasi per-baris (`sumber_volume`, hasil `ImportFormulaRfPkb.php`) tetap murni interpretasi manual terhadap teks formula — divalidasi secara TIDAK LANGSUNG lewat kecocokan ΣWpv total 4 desimal di 4 sheet sekaligus (kecocokan kebetulan sedetail itu di 4 dataset independen secara statistik sangat tidak mungkin kalau kategorisasinya salah), bukan diverifikasi otomatis oleh engine manapun.
- Diverifikasi lewat Tinker: `hitungKebutuhanFormasi()` dipanggil langsung lewat reflection dengan data Kabupaten Bandung, ΣWpv match persis ke Excel untuk 4 jenjang, kebutuhan_bulat terkonfirmasi ROUNDUP semua jenjang (Pemula 5, Terampil 22, Mahir 7, Penyelia 6). Full `store()` ditest 2 skenario (Kemenhub unit id=1700 tanpa upload pegawai; Dishub unit id=1701 dengan upload 2 pegawai + 1 baris kosong yang harus ter-skip) di dalam `DB::beginTransaction()`...`DB::rollBack()`. `show()`/`edit()`/`update()`/`updateHasilFinal()` ditest berurutan: override manual `formasi_final` (22→21) tersimpan tanpa mengubah `formasi_sistem`; admin_unit dipastikan 403 saat mencoba `updateHasilFinal()`; override dipastikan BERTAHAN setelah `update()` (edit variabel) dijalankan ulang oleh admin_unit. Otorisasi admin_unit lintas-unit ditest (403 terkonfirmasi). Gating UI tombol "+ Buat Usulan" ditest untuk role `kabid_perencanaan_jft` & `viewer`.
- **BELUM diuji:** interaksi sungguhan di browser (isi form, klik submit, lihat hasil kalkulasi tampil di halaman show, toggle Step 3 saat pilih unit kerja Dishub vs Kemenhub lewat dropdown Admin Pusbin, klik submit form override Formasi Final).
- `RekomendasiFormasiController::update()` sengaja TIDAK mengubah data pegawai yang sudah diupload (itu sudah permanen di modul Pegawai JFT terpisah) — edit usulan hanya mengubah variabel beban kerja & angka usulan admin unit, lalu hitung ulang.

---

## Versi 1.23.0 - RF-1A: Fondasi Database, Digitalisasi Rumus PKB, Role Baru
**Tanggal:** 3 Agustus 2026
**Status:** ⚠️ Sebagian diverifikasi saat itu (lihat catatan RF-1B di atas — sebagian hasil ekstraksi ternyata masih keliru, sudah diperbaiki di v1.24.0). Dicatat retroaktif -- sempat terlewat masuk CHANGELOG saat sesi RF-1A berlangsung.

---

## Ringkasan

Fondasi modul baru **Rekomendasi Formasi (RF-01)** — unit kerja (Kemenhub maupun Dinas Perhubungan) mengusulkan penambahan formasi, dimulai dari rumus JF Penguji Kendaraan Bermotor (PKB). Desain dibuat extensible untuk JF lain di masa depan.

---

## Perubahan

### A. Struktur Database

- **BARU** `database/migrations/2026_08_03_000001_create_rekomendasi_formasi_tables.php` — 6 tabel: `formula_rf_master` (rumus per JF, extensible), `rekomendasi_formasi_usulan` (induk), `rekomendasi_formasi_variabel` (input beban kerja), `rekomendasi_formasi_hasil` (hasil kalkulasi per jenjang), `rekomendasi_formasi_pegawai_existing` (audit trail upload Dishub), `rekomendasi_formasi_berita_acara` (TTD digital, belum dipakai sampai fase verifikasi/BA dibangun).

### B. Digitalisasi Rumus PKB dari Excel

- **BARU** `app/Console/Commands/ImportFormulaRfPkb.php`, `app/Models/FormulaRfMaster.php` — baca 4 sheet (PEMULA/TERAMPIL/MAHIR/PENYELIA_DISHUB) dari `database/seeders/data/formula_rf_pkb_referensi.xlsx`, ekstrak butir kegiatan + waktu + pemetaan sumber volume (uji_pertama/uji_reguler/kb_diuji_total/bbm_bensin/bbm_solar/konstanta_hari_kerja).
- Hasil awal: 116 baris. **Lihat v1.24.0 untuk koreksi menyeluruh** — beberapa aspek ekstraksi awal ini (fallback volume, asumsi kolom, asumsi pembulatan) ternyata perlu direvisi setelah divalidasi ketat terhadap angka resmi Excel.

### C. Role Baru

- Role `kabid_perencanaan_jft` dibuat (representasi Kepala Bidang Perencanaan & Pembentukan JFT Pusbin, penanda tangan Berita Acara pihak Pusbin).
- **UBAH** `database/seeders/RolePermissionUpdateSeeder.php` — tambah 5 permission baru (`view/create/edit/verifikasi/ttd rekomendasi formasi`), dibagi ke role terkait (admin_unit: view+create+edit; admin: view+verifikasi; kabid_perencanaan_jft: view+verifikasi+ttd; viewer: view).

---

## Versi 1.22.0 - Fix Dashboard "Perlu Tindakan" (DASH-02)
**Tanggal:** 3 Agustus 2026
**Status:** ✅ Ditest end-to-end di browser sungguhan (Chrome via `smartjft.test`, Laragon) untuk ketiga role — Super Admin, Admin Pusbin, Admin Unit. Semua widget "Perlu Tindakan" tampil benar, angka konsisten dengan data DB, dan link kartu diklik langsung untuk memastikan mengarah ke halaman yang tepat. Ditemukan 1 bug pra-eksisting tak terkait (lihat Catatan Teknis) — dicatat, tidak diperbaiki karena di luar scope DASH-02.

---

## Ringkasan

Diagnosa & perbaikan widget "Perlu Tindakan" di dashboard Super Admin/Admin Pusbin dan Admin Unit. Ditemukan 2 masalah berbeda sifat: satu widget under-count (nilai status tidak lengkap pasca-restrukturisasi Pengangkatan JFT v1.14.0), satu widget belum pernah dibuat sama sekali untuk Admin Unit. Tidak ditemukan nilai status basi/lama peninggalan sebelum restrukturisasi — seluruh nilai status lain yang dipakai dashboard sudah valid dan sesuai alur controller aktual.

---

## Perubahan

### A. Fix Under-count: Permohonan Pengangkatan Pending (Super Admin / Admin Pusbin)

- **UBAH** `app/Http/Controllers/PetaDashboardController.php` — `index()`: query `permohonan_pengangkatan_pending` sebelumnya cuma `where('status', 'diajukan')`, mengabaikan status `'menunggu_ttd'` yang juga tahap "perlu tindakan Pusbin" (generate surat rekomendasi + konfirmasi TTD, sesuai alur 4-tahap Pengangkatan JFT sejak v1.14.0: Draft→Diajukan→Menunggu TTD→Selesai). Diperbaiki jadi `whereIn('status', ['diajukan', 'menunggu_ttd'])`.
- **UBAH** `resources/views/users/dashboard.blade.php` — link kartu disesuaikan: sebelumnya `route('pengangkatan.index', ['status' => 'diajukan'])` (cuma tampilkan sebagian dari yang dihitung), diubah jadi `route('pengangkatan.index')` tanpa filter status karena route itu cuma dukung filter status tunggal, sementara angka baru mewakili 2 status sekaligus. Label ditambah keterangan "(Diajukan/TTD)" agar jelas cakupannya.

### B. Fix Widget Hilang: Permohonan Pengangkatan Berjalan (Admin Unit)

- **Diagnosa:** `dashboardAdminUnit()` sebelumnya sama sekali tidak query data Pengangkatan JFT — hanya ada 4 stat card soal Ujikom (Total Pemangku, Menunggu Verifikasi, Diproses Pusbin, Selesai). Padahal Admin Unit adalah pihak yang mengajukan permohonan Pengangkatan (lihat `PengangkatanController::create()` — form otomatis terikat ke `unit_kerja_id` Admin Unit yang login), sehingga wajar mereka perlu melihat status permohonannya sendiri di dashboard.
- **UBAH** `app/Http/Controllers/PetaDashboardController.php` — `dashboardAdminUnit()`: tambah query `$pengangkatanBerjalan = PengangkatanPermohonan::where('unit_kerja_id', $unitKerjaId)->whereNotIn('status', ['selesai', 'ditolak'])->count()`, dikirim ke view.
- **UBAH** `resources/views/users/dashboard.blade.php` — tambah stat card ke-5 "Permohonan Pengangkatan Berjalan" di blok `@role('admin_unit')`, link ke `route('pengangkatan.index')` (sudah otomatis terfilter ke unit sendiri lewat scoping di controller).

---

## Catatan Teknis

- **Bukan bug nilai status basi** — ditelusuri sampai ke migration & alur controller nyata: `ujikom_pendaftaran.status`, `ujikom_hasil.status_kelulusan`, `ujikom_sesi.status_sesi`, `ujikom_jadwal.status` semuanya sudah dipakai dengan nilai yang valid dan sesuai alur aktual (termasuk temuan bahwa status `diverifikasi_admin_unit` di enum `ujikom_pendaftaran` adalah status mati — endpoint `ajukanPusbin()` yang membutuhkannya terdaftar di route tapi tidak pernah dipanggil dari view manapun; alur nyata `verifikasiAdminUnit()` langsung lompat dari `diajukan_admin_unit` ke `diajukan_pusbin`). Hanya `pengangkatan_permohonan.status` yang punya masalah, dan itu bukan nilai salah — cuma daftar status di query yang tidak lengkap pasca migration `2026_07_13_000001_simplify_pengangkatan_status_and_ranking.php`.
- **`hasil_belum_dinilai` (Super Admin/Admin Pusbin) dikonfirmasi SUDAH benar** — ditelusuri ke `UjikomOnlineController::cobaFinalisasiHasil()`: status `belum_dinilai` di-set persis saat sesi CAT+Mansoskul selesai tapi masih menunggu nilai manual Wawancara/Presentasi yang aktif di jadwal (bukan status generik "belum dinilai apa-apa").
- Diverifikasi lewat Tinker (sebelum test browser): render penuh `PetaDashboardController::index()` untuk Super Admin, Admin Pusbin (role `admin`), dan Admin Unit (user dengan `unit_kerja_id` terisi — ditemukan ada user ber-role `admin_unit` tanpa `unit_kerja_id` di database, dihindari untuk test ini karena tidak representatif). Karena data pengujian di database cuma ada 1 record `pengangkatan_permohonan` (status `selesai`), dibuat 2 record uji sementara (status `menunggu_ttd` & `diajukan`) di dalam transaksi DB (`DB::beginTransaction()` ... `DB::rollBack()`) untuk membuktikan angka di kedua widget baru benar-benar bertambah sesuai ekspektasi, lalu di-rollback — jumlah record di tabel kembali ke 1 setelah verifikasi.
- **Diverifikasi lewat browser sungguhan (Chrome, via Claude in Chrome, 3 Agustus 2026):**
  - Login sebagai Super Admin & Admin Pusbin (akun default) — kartu "Permohonan Pengangkatan Menunggu (Diajukan/TTD)" tampil 0 (cocok dengan DB, karena 1 record asli berstatus `selesai`), diklik → mendarat di `/pengangkatan` dengan angka Total Permohonan=1 yang konsisten. Console bersih tanpa error.
  - Login sebagai Admin Unit — karena tidak ada kredensial default untuk role ini dan user ber-`unit_kerja_id` yang ada adalah akun pribadi user (bukan akun uji), dibuatkan **akun uji sementara** (`temp.admin.unit.test@smartjft.local`, `unit_kerja_id=1700`) khusus untuk login browser, **dihapus permanen (`forceDelete`) setelah testing selesai** — dikonfirmasi jumlah user kembali seperti semula. Kartu ke-5 "Permohonan Pengangkatan Berjalan" tampil benar (0, layout wrap ke baris baru secara wajar di grid Bootstrap 4), diklik → mendarat di `/pengangkatan` dengan daftar kosong khusus unit tersebut (terpisah dari data unit lain), sesuai scoping.
  - **Ditemukan bug pra-eksisting tak terkait DASH-02** (bukan regresi dari perubahan sesi ini): console browser Admin Unit menunjukkan `Error: Map container not found` dari Leaflet — script inisialisasi peta (`resources/views/users/dashboard.blade.php`, `@push('scripts')` sekitar baris 795) berjalan tanpa syarat untuk semua role, padahal div `#leafletMap-dashboard` cuma dirender di dalam blok `@hasanyrole('super_admin|admin|viewer')`. Untuk role `admin_unit`/`pemangku`, script coba `L.map()` ke elemen yang tidak ada di DOM. Tidak menghalangi rendering dashboard atau widget "Perlu Tindakan" (keduanya tetap tampil normal), tapi tercatat di sini sebagai temuan terpisah untuk sesi berikutnya — **belum diperbaiki, di luar scope DASH-02**.

---

## Versi 1.21.0 - Fix Modul Laporan Terpadu (LAP-01) & Ekspansi 3 Tab Transaksional
**Tanggal:** 26 Juli 2026
**Status:** ✅ Ditest end-to-end di browser sungguhan (Chrome, login Super Admin) — ke-7 tab dicek satu per satu, render benar, tidak ada error console tersisa. Export PDF/Excel belum diklik langsung di browser (baru diverifikasi via Tinker), lihat Catatan Teknis.

---

## Ringkasan

Diagnosa menyeluruh terhadap modul "Laporan Terpadu" (4 tab: Dashboard/Unit Kerja/Formasi/Pegawai JFT) yang sebelumnya dibangun di sesi tanpa dokumentasi CHANGELOG (gap serupa dengan yang ditemukan untuk tema UI di v1.20.0). Hasil diagnosa: data & fitur ekspor PDF/Excel sudah berfungsi penuh, masalah murni ketidakkonsistenan tema (sintaks Bootstrap 5 belum dikonversi ke Bootstrap 4 + `do-layout.css`). Selain itu ditemukan dan diperbaiki bug fatal aktif yang tidak terkait Laporan, lalu ditambahkan 3 tab baru untuk data transaksional (Uji Kompetensi, Pengangkatan JFT, Pendaftaran Ujikom).

---

## Perubahan

### A. Bug Fatal Tersembunyi — Ditemukan & Diperbaiki di Luar Scope Awal

- **Root cause:** `app/Models/PengangkatanPermohonan_lama.php`, `PengangkatanSurat_lama.php`, `PengangkatanPeserta_lama.php`, dan `app/Http/Controllers/PengangkatanController_lama.php` adalah file mati peninggalan rombak v1.14.0 yang tertinggal langsung di `app/` (bukan `_archive/` sesuai konvensi v1.13.0). 2 di antaranya (`PengangkatanPermohonan_lama`, `PengangkatanSurat_lama`) mendeklarasikan nama class **persis sama** dengan model aktif. Dikombinasikan dengan `"optimize-autoloader": true` di `composer.json`, ini menyebabkan fatal error intermiten `Cannot declare class ... because the name is already in use` — terekam berulang kali di `laravel.log`, termasuk beberapa kali pada 26 Juli 2026 sebelum sesi ini.
- **Fix:** ke-4 file dipindahkan ke `_archive/models_controllers_lama_pengangkatan/` (bukan dihapus). Diverifikasi tidak ada referensi aktif ke file-file ini di `routes/web.php` atau tempat lain sebelum dipindah; `php artisan route:list` dan instansiasi model dicek ulang setelahnya, bersih.

### B. Perbaikan 4 Tab Lama (Dashboard, Unit Kerja, Formasi, Pegawai JFT)

- **Diagnosa:** logika data controller (`LaporanController`) sudah benar dan berfungsi — bukan bug fungsional. Log error lama soal Laporan (`Target class does not exist`, `syntax error =>`, dsb.) semuanya bertanggal Maret 2026, sudah usang. Fitur ekspor PDF (DomPDF, 4 view `laporan/pdf/*.blade.php`) dan Excel (`LaporanExcelExport` dengan `WithMultipleSheets`) **sudah ada dan berfungsi penuh** sebelum sesi ini — tidak dibuat ulang.
- **UBAH** `resources/views/laporan/index.blade.php` — reskin total ke Bootstrap 4 + tema `do-layout.css` (`preview-card`/`preview-header`/`preview-body`/`do-badge`/`subsection-label`), konsisten dengan Users/Formasi/SDM (v1.20.0). Sintaks Bootstrap 5 yang sebelumnya terpakai (`form-select`, `gap-2`, `ms-2`, `fw-bold`, `row g-3`, badge `bg-success`/`bg-danger`) dikonversi ke padanan Bootstrap 4 (`form-control`, `mr-*`/`ml-*`, `font-weight-bold`, `do-badge` inline-style). Semua `name` input, route, dan `@can` dipertahankan persis — nav-tabs bertambah dari 4 menjadi 7 item.

### C. Tab Baru — Data Transaksional

- **Tab 5 — Uji Kompetensi:** filter Jadwal Ujikom/Tahun/Jenjang/Unit Kerja. Ringkasan (total jadwal, total peserta, tingkat kelulusan %, jumlah sesi terindikasi kecurangan), grafik tren kelulusan per tahun (Chart.js line), breakdown rata-rata nilai per aspek (CAT/Wawancara/Presentasi) dan per kompetensi (Teknis/Mansoskul), tabel rekap per jadwal.
- **Tab 6 — Pengangkatan JFT:** filter Tahun/Unit Kerja/Jabatan. Ringkasan (total permohonan, total diangkat, rata-rata waktu proses dari `tanggal_permohonan` ke `tanggal_disetujui`), grafik tren jumlah pengangkatan per tahun, tabel rekap per unit kerja/jabatan/jenjang. **Filter & breakdown "Jalur Pengangkatan" (7 jalur) TIDAK dibuat** — lihat Catatan Teknis.
- **Tab 7 — Pendaftaran Ujikom:** filter Tahun/Unit Kerja. Ringkasan (total permohonan, breakdown 8 status, tingkat penolakan %), tabel permohonan belum selesai diurutkan dari paling lama menunggu, tabel catatan penolakan (teks bebas). **Rata-rata waktu verifikasi per tahap TIDAK dihitung** — lihat Catatan Teknis.
- **BARU** `resources/views/laporan/pdf/ujikom.blade.php`, `pengangkatan.blade.php`, `pendaftaran.blade.php` — mengikuti pola existing (kop surat, filter info, tabel).
- **UBAH** `app/Exports/LaporanExcelExport.php` — tambah 3 sheet class: `UjikomSheet`, `PengangkatanSheet`, `PendaftaranSheet`.
- **UBAH** `app/Http/Controllers/LaporanController.php` — tambah `getUjikomData()`, `getPengangkatanData()`, `getPendaftaranData()`, `jenjangTujuanOptions()`; extend `getPdfData()`/`getTabTitle()`/`getFilterParams()` untuk 3 tab baru. Tidak ada perubahan route baru (tab 5-7 memakai route `export-pdf`/`export-excel` yang sama dengan parameter `{tab}` berbeda).

### D. Bug Ditemukan Saat Test Browser Sungguhan & Diperbaiki

- **Root cause:** kedua chart di Tab 1 Dashboard (bar Kuota vs Terisi & pie Distribusi Jenjang) gagal render total — canvas kosong tanpa error PHP apapun. Console browser menunjukkan `SyntaxError: Unexpected token ':'`. Penyebab: baris `const baseUrl = {{ route('user.wilayah.regencies', [...]) }};` di blok `@push('scripts')` menghasilkan URL mentah tanpa tanda kutip di JS (`const baseUrl = http://...;`), yang secara sintaksis invalid — titik dua setelah `http` dibaca sebagai token tak terduga. Ini menyebabkan seluruh `<script>` block gagal di-parse browser, termasuk kode inisialisasi Chart.js yang berada di atasnya dalam file yang sama.
- **Penting:** bug ini **sudah ada di kode original sebelum sesi ini** (persis sama, 3 kali, di baris yang identik untuk tab Dashboard/Unit Kerja/Formasi) — bukan regresi baru. Lolos dari diagnosa Tahap A/Tinker sebelumnya karena Tinker hanya mengecek compile PHP & render HTML, tidak menjalankan JavaScript di browser sungguhan.
- **Fix:** `resources/views/laporan/index.blade.php` — baris di-ubah jadi `const baseUrl = @json(route('user.wilayah.regencies', ['province' => '__PID__']));` (satu baris, karena ketiga blok cascading provinsi→kab/kota sudah dikonsolidasi jadi satu helper `wireCascade()` saat reskin Tahap B). Diverifikasi ulang di browser: kedua chart render normal setelah `php artisan view:clear`, console bersih dari error aplikasi.

---

## Catatan Teknis

- **Keterbatasan data (dilaporkan, bukan dipaksakan):**
  - **Jalur Pengangkatan (Tab 6):** kolom `jalur` sudah dihapus total dari `pengangkatan_permohonan`/`pengangkatan_kandidat` sejak penyederhanaan alur v1.14.0 (tanpa ranking, 4 status saja). Field ini masih ada di model mati `PengangkatanPermohonan_lama.php` (7 nilai enum) tapi tidak pernah dipakai lagi di skema aktif. **Disepakati dengan user (26 Juli 2026): skip filter/breakdown ini tanpa migration baru**, bukan diasumsikan/dipaksakan.
  - **Rata-rata waktu verifikasi per tahap (Tab 7):** `ujikom_pendaftaran` hanya menyimpan status **terakhir** + `created_at`/`updated_at`, tanpa timestamp per transisi status (Draft→Diajukan Admin Unit→Diverifikasi Admin Unit→dst, 8 status total). Metrik ini secara eksplisit dilaporkan tidak bisa dihitung akurat, ditampilkan sebagai alert keterbatasan di UI dan PDF, bukan dipaksakan jadi angka palsu.
  - **Alasan penolakan terbanyak (Tab 7):** `catatan_admin_unit`/`catatan_pusbin` adalah teks bebas, bukan kolom enum terstruktur — sehingga tidak dibuat ranking kategori otomatis (berisiko salah kategorisasi). Ditampilkan sebagai daftar catatan mentah per permohonan ditolak.
  - Sebaliknya, **rata-rata waktu proses Pengangkatan JFT (Tab 6) bisa dihitung akurat** karena `tanggal_permohonan` dan `tanggal_disetujui` tersimpan lengkap di `pengangkatan_permohonan`.
- **Gap dokumentasi ditemukan:** modul Laporan Terpadu (4 tab + ekspor PDF/Excel) ternyata sudah dibangun penuh di sesi sebelumnya tanpa pernah masuk root CHANGELOG.md — sama seperti gap yang dicatat untuk tema UI di v1.20.0. Versi lama "Laporan Terpadu" yang tercatat di riwayat dokumentasi lama (v1.3.0, "PAUSED — Error belum teridentifikasi") adalah implementasi berbeda yang sudah lama digantikan.
- Diverifikasi lewat Tinker: instansiasi & render penuh `LaporanController::index()` (dengan dan tanpa query filter) sebagai user Super Admin, render langsung ke-3 view PDF baru, panggilan langsung `exportPdf()`/`exportExcel()` untuk ketiga tab baru (hasil: `Illuminate\Http\Response` dan `Symfony\Component\HttpFoundation\BinaryFileResponse`, tidak ada exception), serta `php artisan route:list` dan re-render ulang setelah `php artisan optimize:clear`.
- **Diverifikasi lewat browser sungguhan (Chrome, via Claude in Chrome, 26 Juli 2026):** login sebagai Super Admin, buka `/user/laporan`, klik satu per satu ke-7 tab (Dashboard, Unit Kerja, Formasi, Pegawai JFT, Uji Kompetensi, Pengangkatan JFT, Pendaftaran Ujikom). Semua tab menampilkan data asli dengan benar, filter dropdown terisi, kedua chart Dashboard dan chart Tab 5/6 render sempurna, notice keterbatasan data (jalur pengangkatan & timestamp verifikasi) tampil sesuai rancangan, console browser bersih dari error aplikasi (hanya warning tak terkait dari ekstensi Chrome pihak ketiga). Server sementara `php artisan serve` (port 8123) dipakai selama testing karena Apache/nginx Laragon belum aktif saat itu — sudah dimatikan (PID 10100) setelah user mengaktifkan Laragon kembali.
- **BELUM diuji:** klik langsung tombol Export PDF/Excel di browser dan verifikasi isi file unduhan (baru diverifikasi via Tinker bahwa response type-nya benar, belum dibuka manual filenya), tampilan responsif do-layout theme di ukuran layar mobile/tablet.
- MySQL Laragon sempat tidak aktif di awal sesi ini — dinyalakan manual (`mysqld.exe`) untuk keperluan diagnosa & testing; sudah kembali dikelola Laragon setelah user mengaktifkannya ulang.

---

## Versi 1.20.0 - Tema UI Digital Office (E-Kinerja) & Rangkuman Progres Menyeluruh
**Tanggal:** 16 Juli 2026
**Status:** Sebagian teruji — Tahap 5 Bagian 1 (base layout & sidebar) sudah dicek user langsung di browser dan dikonfirmasi berfungsi. Bagian 2A-2C (tabel, form, detail/trash) BELUM ditest end-to-end oleh user, jangan anggap selesai sampai ada konfirmasi.

---

## Ringkasan

Mencatat progres UI/UX besar yang belum pernah didokumentasikan di root CHANGELOG — implementasi tema visual baru "E-Kinerja" (Digital Office Kemenhub) sebagai lapisan di atas AdminLTE + Bootstrap 4 yang sudah berjalan, tanpa mengubah identitas SMART JFT/Pusbin JFT — sekaligus merangkum arah besar pekerjaan pada rentang v1.12.0-v1.19.0 sebagai satu narasi utuh untuk referensi cepat.

---

## Perubahan

### A. Tema UI/UX — Digital Office "E-Kinerja" (BARU, belum tercatat sebelumnya)

- **Tahap 5 Bagian 1 — Base Layout & Sidebar** *(sudah dicek user, berfungsi)*
  - `resources/views/layouts/users/master.blade.php` direstrukturisasi total: topbar baru bergaya Digital Office (identitas SMART JFT, bukan SSO asli), sidebar direstrukturisasi memakai kelas dari `do-layout.css` (`sidebar-header-custom`, `nav-link`, `nav-sub-link`, `category-header-custom`, dst.), font Plus Jakarta Sans + Bootstrap Icons ditambahkan
  - Semua route/menu/`@can`/`@hasanyrole`/`@role` dipertahankan 100% — murni perubahan visual/struktur HTML
  - JS baru `toggleSidebar()`/`toggleSubmenu()` menggantikan widget `data-widget="pushmenu"`/`"treeview"` bawaan AdminLTE (tidak relevan lagi karena struktur DOM sidebar berubah total)
  - **BARU** `public/library/dist/css/smartjft-theme.css` — override warna primer (`#0c2d47`) di atas tema E-Kinerja
  - **Penyesuaian penting:** markup contoh tema E-Kinerja yang diterima mengasumsikan Bootstrap 5 (`data-bs-*`, `dropdown-menu-end`, utility `gap-*`) — seluruh project ini murni Bootstrap 4, sehingga semua markup baru disesuaikan (`data-toggle`, `dropdown-menu-right`, `mr-*`/`ml-*`) agar tidak merusak halaman lain yang sudah ada
- **Tahap 5 Bagian 2A — Tabel Index** *(belum ditest)*
  - `resources/views/users/index.blade.php`, `formasi_jabatan/index.blade.php`, `sdm/index.blade.php` dibungkus kartu `preview-card`/`preview-header`/`preview-body`, badge status memakai `do-badge`, tombol aksi dirapikan jadi grup kecil konsisten
  - Semua `id` DataTables, variabel filter, dan `@can`/route dipertahankan persis
- **Tahap 5 Bagian 2B — Form Tambah/Edit** *(belum ditest)*
  - `users/create.blade.php`, `users/edit.blade.php`, `formasi_jabatan/create.blade.php`, `sdm/form.blade.php` dibungkus `preview-card` dengan label subseksi (`subsection-label`); `sdm/create.blade.php`/`edit.blade.php` ikut disesuaikan karena tag `<form>` ada di situ, bukan di partial `sdm/form.blade.php`
  - Semua `name` input, `old()`/`@error()`, dan JS cascading (provinsi→kab/kota, toggle Formasi/Unit Kerja di SDM, tambah/hapus baris dinamis di Formasi) dipertahankan persis
- **Tahap 5 Bagian 2C — Detail & Trash** *(belum ditest)*
  - `users/show.blade.php` dan 3 halaman Trash (`users`, `formasi_jabatan`, `sdm`) dibungkus pola `preview-card` yang sama; tombol Restore/Hapus Permanen dirapikan jadi grup ikon kecil

### B. Bug Tersembunyi yang Ditemukan & Diperbaiki Selama Proses Restyle (BARU)

- `users/show.blade.php`: judul halaman memakai `Auth::user()->role` — kolom itu **sudah tidak ada** di tabel `users` sejak migrasi ke Spatie Permission (v1.1.0), sehingga kondisi selalu `false` dan judul selalu menampilkan "USER" walau yang login admin. Diperbaiki ke `Auth::user()->hasAnyRole(['admin','super_admin'])`
- `users/show.blade.php`: peta Leaflet di halaman Detail Unit Kerja kemungkinan besar **tidak pernah render** — skrip `L.map(...)` ditulis inline di posisi yang dieksekusi sebelum library Leaflet dimuat (Leaflet baru dimuat lewat `@stack('leaflet')` dekat akhir `<body>`). Diperbaiki dengan memindahkan skrip ke `@push('scripts')`
- `users/create.blade.php`: elemen `<div id="leafletMap-registration">` sebelumnya dibungkus `<table>` — HTML tidak valid (div langsung di dalam table tanpa tr/td). Diperbaiki jadi div biasa, ID peta dipertahankan persis agar binding JS tidak putus
- `users/edit.blade.php`: 2 field mati ter-comment (`jam_kerja`, `fasilitas`) yang mereferensikan kolom yang tidak pernah ada di tabel `unit_kerja` — dihapus
- `sdm/form.blade.php`: 89 baris pertama file adalah blok komentar berisi duplikat persis dari form aktif di bawahnya (kode mati total) — dihapus
- `sdm/create.blade.php`/`edit.blade.php`: sebelumnya tidak ada alert ringkasan error (`@if($errors->any())`), hanya error per-field — ditambahkan, konsisten dengan modul lain
- `formasi_jabatan/index.blade.php`: `<div>` toolbar filter yang tidak pernah ditutup dengan `</div>` (browser otomatis "memperbaiki" secara visual selama ini) — diperbaiki strukturnya

### C. Ringkasan Progres Sebelumnya (sudah tercatat lengkap di versi masing-masing, direkap di sini untuk konteks)

1. **Pengangkatan JFT — Penyederhanaan Alur** *(detail lengkap: v1.14.0)*
   - Ranking otomatis berbasis nilai ujikom dihapus total
   - Alur baru 4 tahap: Draft → Diajukan → Menunggu TTD → Selesai (dari 6 tahap sebelumnya)
   - Validasi kuota formasi dilakukan di awal saat Admin Unit mengajukan peserta, bukan setelah diproses Pusbin
   - Admin Pusbin kini hanya generate surat rekomendasi + konfirmasi TTD, tanpa proses seleksi/ranking manual
2. **Modul Ujian — Restrukturisasi Menyeluruh** *(detail lengkap: v1.15.0-v1.18.0)*
   - Bank Soal: `tingkat_kesulitan` dihapus total, istilah Umum/Spesifik → Mansoskul/Teknis, tambah kolom `matra` + `nilai_pilihan_a-d` (skala 1-5, khusus soal Mansoskul)
   - Kategori Bank Soal kini murni khusus untuk soal Teknis
   - Paket Ujian: mode baru "2 Sesi CAT" (Teknis + Mansoskul dalam 1 paket), komposisi taksonomi Bloom dihitung otomatis berbobot berjenjang
   - Jadwal Ujikom: konfigurasi aspek penilaian (CAT/Wawancara/Presentasi) per kompetensi
   - Ujikom Online: alur 2 sesi CAT berurutan, skoring Mansoskul skala 1-5, sistem anti-kecurangan (deteksi pindah tab/minimize/kamera mati, batas 3x pelanggaran, auto-submit — status auto-submit ini dipisah jadi `disubmit_paksa` di v1.19.0 supaya tidak tertukar makna dengan "waktu habis")
   - Hasil Ujikom: struktur nilai berlapis (per aspek → per kompetensi → nilai akhir), indikator status kecurangan
3. **Fitur Import Excel & Template** *(detail lengkap: v1.19.0)*
   - Unit Kerja: antarmuka web Import Excel + download template (baru — sebelumnya hanya bisa lewat Artisan command)
   - Formasi & Pegawai JFT: download template Excel (fitur import-nya sendiri sudah ada sebelum v1.19.0)
4. **Rename Penamaan "RumahSakit" → "UnitKerja"** *(detail lengkap: v1.13.0)*
   - Model, Controller, tabel, kolom, primary key (`no_rs` → `id`) di-rename menyeluruh di seluruh lapisan aplikasi
   - File mati/backup dipindahkan ke folder `_archive/`, bukan dihapus

---

## Catatan Teknis

- Bagian A "Tahap 5 Bagian 1" sudah dicek user langsung di browser dan dikonfirmasi berfungsi. Bagian 2A/2B/2C **belum** dikonfirmasi user — status tetap dianggap belum final sampai ada konfirmasi test dari user.
- Semua perbaikan di Bagian B diverifikasi lewat render langsung (tinker, bukan cuma compile check `view:cache`) sebelum dianggap selesai.
- Bagian C murni rekap untuk konteks — TIDAK menggantikan detail asli di versi yang dirujuk, lihat versi masing-masing untuk rincian penuh (file, migration, dan catatan teknis lengkap).

---

## Versi 1.19.0 - Perbaikan Anti-Cheat, Gerbang Buka Sesi, Dashboard Pemangku & Import Excel
**Tanggal:** 16 Juli 2026
**Status:** ⚠️ BELUM DITEST — kode sudah diverifikasi lewat tinker/unit-level (tidak ada error, alur data benar), tapi BELUM dicoba end-to-end di browser oleh user. Jangan anggap selesai sampai user mengonfirmasi hasil test.

---

## Ringkasan

Empat batch perbaikan/fitur pada modul Ujikom Online (CAT engine), Dashboard Pemangku, dan Import Excel (Unit Kerja/Formasi/Pegawai JFT). Beberapa diagnosa awal di prompt ternyata tidak sesuai kode sungguhan (lihat Catatan Teknis tiap bagian) — implementasi disesuaikan dengan kondisi kode nyata, bukan mengikuti asumsi literal.

---

## Perubahan

### 1. Anti-Cheat — Status Sesi, Kamera Wajib, Peringatan Pelanggaran

**Database**
- **BARU** `database/migrations/2026_07_15_000001_add_disubmit_paksa_to_ujikom_sesi_status.php` — tambah nilai enum `disubmit_paksa` di `ujikom_sesi.status_sesi`, memisahkan makna "disubmit paksa akibat 3x pelanggaran anti-cheat" dari `timeout` ("waktu ujian benar-benar habis") yang sebelumnya memakai nilai yang sama

**Model & Controller**
- **UBAH** `app/Models/UjikomSesi.php` — `getLabelStatusSesiAttribute()`/`getBadgeStatusSesiAttribute()`/`getSisaWaktuAttribute()` tambah case `disubmit_paksa`
- **UBAH** `app/Http/Controllers/UjikomOnlineController.php`
  - `submit()`: sebelumnya hardcode status `'selesai'` tanpa mengecek alasan request — kalau timer JS di browser mencapai 0 dan submit form yang sama terkirim, sesi salah tercatat `'selesai'` padahal waktu sudah habis. Sekarang menentukan status dari `batas_waktu` vs waktu server (`Carbon::now()`), bukan asumsi client
  - `catatPelanggaran()`: pelanggaran ke-3 sekarang set `'disubmit_paksa'`, bukan `'timeout'`
  - Semua pengecekan status terminal `['selesai','timeout']` diperluas jadi `['selesai','timeout','disubmit_paksa']` (7 titik)

**View**
- **UBAH** `resources/views/ujikom/online/ujian.blade.php`
  - Kamera: sebelumnya kalau izin kamera ditolak/gagal di awal, hanya lapor **1x pelanggaran** lalu tidak pernah dicek ulang (interval pengecekan cuma didaftarkan setelah `getUserMedia` sukses) — peserta bisa selesai ujian dengan kamera mati total memakai cuma 1 dari 3 jatah pelanggaran. Sekarang ada overlay penuh layar non-dismissable (`.camera-block-overlay`) yang memblokir interaksi, pengecekan tiap 5 detik berjalan terus-menerus, dan sistem otomatis mencoba mengaktifkan ulang kamera
  - Peringatan pelanggaran: sudah memakai `<div>` custom (bukan `alert()` native) sejak awal, tapi gaya visualnya memakai Bootstrap `alert-danger` pudar — diganti kelas `.anti-cheat-warning-box` (merah solid, teks putih tebal, sesuai spesifikasi)
- **UBAH** `resources/views/ujikom/online/hasil.blade.php`, `index.blade.php`, `monitoring.blade.php` — badge/label untuk status `disubmit_paksa`, array status terminal diperluas

### 2. Gerbang "Buka Sesi" Admin Sebelum Peserta Bisa Mulai Ujian

- **UBAH** `app/Http/Controllers/UjikomOnlineController.php` — `mulai()`: root cause bug adalah baris `else { $sesi = UjikomSesi::create(...) }` yang membuat sesi baru sendiri kalau admin belum pernah `bukaSesi()` — komentar kode sudah menyebut "cek apakah sesi sudah dibuka admin" tapi tidak pernah benar-benar memblokir. Sekarang kalau belum ada baris `ujikom_sesi` sama sekali, peserta ditolak dengan pesan "Sesi ujian belum dibuka oleh Admin Pusbin..."
- **UBAH** `resources/views/ujikom/online/index.blade.php` — badge status eksplisit (Belum Dibuka/Sedang Berlangsung/Ditutup) di samping tombol Buka/Tutup Sesi yang sudah ada sebelumnya
- **UBAH** `resources/views/ujikom/jadwal/index.blade.php`, `show.blade.php` — badge status sesi online + tombol Buka/Tutup Sesi (memakai ulang route admin yang sudah ada, bukan duplikasi logic)

### 3. Dashboard Pemangku — Jadwal Terdekat Tidak Pernah Muncul

- **UBAH** `app/Http/Controllers/PetaDashboardController.php` — `dashboardPemangku()`: query `$jadwalTerdekat` memfilter `status = 'dipublikasikan'`, padahal enum yang benar adalah `'published'` (bug identik dengan yang diperbaiki di v1.12.2, tapi lokasi ini luput saat itu). Akibatnya jadwal terdekat **selalu kosong** sejak fitur ini dibuat. Sisi view (`dashboard.blade.php`) sudah benar dari awal, tidak diubah

### 4. Import Excel & Download Template — Unit Kerja, Formasi, Pegawai JFT

**Baru**
- `app/Imports/UnitKerjaImport.php` — validasi per baris + resolusi `regency_id` dari nama Provinsi/Kab-Kota (logic sama dengan command `GenerateUnitKerjaSeederFromExcel` yang sudah ada, supaya konsisten)
- `app/Exports/UnitKerjaTemplateExport.php`, `FormasiTemplateExport.php`, `PegawaiTemplateExport.php` — masing-masing 2 sheet (Petunjuk + Data), mengikuti gaya styling `BankSoalTemplateExport`
- `resources/views/users/import.blade.php` — halaman upload Import Excel Unit Kerja (mengikuti pola `bank_soal/import.blade.php`, tanpa tabel riwayat log ke database — lihat Catatan Teknis)

**Controller & Route**
- **UBAH** `app/Http/Controllers/UnitKerjaController.php` — `importPage()`, `import()`, `downloadTemplate()`
- **UBAH** `app/Http/Controllers/FormasiJabatanController.php`, `SdmController.php` — `downloadTemplate()`
- **UBAH** `routes/web.php` — 9 route baru (`user.unitkerja.import`, `.import.store`, `.template`, `user.formasi.template`, `user.sdm.template`), static routes ditempatkan sebelum wildcard `/{id}`

**View**
- **UBAH** `resources/views/users/index.blade.php` — tombol "+ Import Excel"
- **UBAH** `resources/views/formasi_jabatan/index.blade.php`, `sdm/index.blade.php` — tombol "Download Template"

---

## Catatan Teknis

- **Diagnosa awal di prompt vs kode sungguhan** — beberapa asumsi tidak akurat, diperbaiki sesuai kondisi nyata setelah verifikasi:
  - `bukaSesi()`/`tutupSesi()` di `UjikomOnlineController` **sudah ada** sebelum prompt FIX-2 — tidak dibuat ulang (akan fatal error "Cannot redeclare method" kalau ditambah lagi)
  - Tombol Edit/Hapus Jadwal Ujikom & Paket Ujian (PERUBAHAN 1 & 2 di prompt FIX-3) **sudah lengkap** dari sebelumnya dengan validasi backend yang benar — tidak ada perubahan
  - Tabel `unit_kerja` tidak punya kolom "jenis UPT"; wilayah disimpan sebagai `regency_id` (FK), bukan teks provinsi/kab-kota
  - Import Formasi yang sudah ada memakai format **pivot** (1 baris = 1 Unit×Jabatan, 8 kolom kuota per jenjang, `tahun_formasi` diisi di form bukan di file) — bukan format flat `unit_kerja_id/jabatan_id/jenjang/kuota/tahun` seperti asumsi prompt
  - Import Pegawai JFT yang sudah ada resolve Unit Kerja & Formasi dari **nama teks**, bukan ID — kolom TMT cuma satu (`tmt_pengangkatan`), bukan "TMT PNS" + "TMT Jabatan" terpisah
- View `resources/views/users/import.blade.php` dipilih (bukan `resources/views/unitkerja/import.blade.php` seperti saran prompt) karena seluruh CRUD Unit Kerja historisnya memang di folder `users/` (peninggalan rename Rumahsakit→UnitKerja v1.13.0)
- Riwayat import Unit Kerja **tidak** dibuatkan tabel log terpisah (beda dari pola penuh Bank Soal) — detail baris gagal ditampilkan lewat flash session di halaman yang sama, cukup untuk kebutuhan saat ini
- Diuji lewat tinker: migration + verifikasi enum kolom, render seluruh view yang diubah (`view:cache` tanpa error), simulasi end-to-end gerbang `mulai()` (blokir sebelum `bukaSesi()`, lolos sesudahnya, 0 sesi liar terbentuk), simulasi dashboard pemangku (jadwal published muncul di HTML), generate ketiga file template Excel, dan import Unit Kerja (1 baris valid + 1 baris gagal, hasil tepat) — semua data uji dirollback/dihapus setelah verifikasi
- **BELUM diuji:** interaksi kamera sungguhan di browser (izinkan/tolak kamera), submit manual vs timer habis sungguhan, klik tombol Buka/Tutup Sesi dari UI, upload file Excel sungguhan lewat form web

---

## Versi 1.18.0 - Hasil Ujikom: Struktur Nilai Berlapis
**Tanggal:** 14 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Halaman Hasil Uji Kompetensi sekarang menampilkan rincian nilai berlapis (CAT/Wawancara/Presentasi per kompetensi Teknis & Mansoskul, plus status kecurangan) lewat struktur accordion per peserta — bukan cuma nilai akhir tunggal. Data rincian disimpan sebagai kolom mentah di `ujikom_hasil` agar bisa diaudit terpisah dari nilai gabungan yang sudah ada sejak v1.16.0.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_14_000001_add_rincian_nilai_to_ujikom_hasil.php` — 6 kolom nullable: `nilai_teknis_cat`, `nilai_teknis_wawancara`, `nilai_teknis_presentasi`, `nilai_mansoskul_cat`, `nilai_mansoskul_wawancara`, `nilai_mansoskul_presentasi` (kolom `status_kecurangan` TIDAK ditambah ulang — sudah ada dari migration v1.17.0, dicek dulu lewat diagnostic sebelum menulis migration supaya tidak dobel)

### Model
- **UBAH** `app/Models/UjikomHasil.php` — fillable/casts 6 kolom baru, accessor `getBadgeKecuranganAttribute()`
- **PERBAIKAN TAMBAHAN (di luar permintaan, wajib)** `app/Models/UjikomSesi.php` — `syncKeHasil()` (alur ujian satu-sesi/`tunggal` dari sebelum v1.17.0) sekarang ikut menghitung `status_kecurangan` dari jumlah pelanggaran anti-cheat; sebelumnya cuma alur 2-sesi (`cobaFinalisasiHasil()`) yang mengisi kolom ini, padahal halaman ini menampilkan status kecurangan untuk SEMUA jenis hasil termasuk yang dari alur lama

### Controller
- **UBAH** `app/Http/Controllers/UjikomOnlineController.php` — `cobaFinalisasiHasil()` mengisi 6 kolom rincian mentah di kedua cabang (belum final maupun final)
- **UBAH** `app/Http/Controllers/UjikomHasilController.php` — `index()` tambah statistik jumlah peserta terindikasi kecurangan per jadwal

### View
- **BARU** `resources/views/ujikom/hasil/_detail_nilai.blade.php` — partial rincian nilai berlapis, dipakai bersama
- **UBAH** `resources/views/ujikom/hasil/show.blade.php` — baris peserta jadi accordion (klik baris → expand rincian Teknis/Mansoskul/Kecurangan), kolom badge Kecurangan
- **UBAH** `resources/views/ujikom/hasil/riwayat.blade.php` — accordion serupa untuk riwayat pribadi peserta (Pemangku JFT) + badge Kecurangan
- **UBAH** `resources/views/ujikom/hasil/index.blade.php` — kolom agregat "Kecurangan" (jumlah peserta terindikasi per jadwal)
- **UBAH** `resources/views/ujikom/hasil/pdf/rekap.blade.php` — kolom rincian Teknis/Mansoskul (CAT/Wawancara/Presentasi) + status kecurangan
- **UBAH** `app/Exports/UjikomHasilExcelExport.php` — 10 kolom baru untuk rincian per aspek + status kecurangan

---

## Catatan Teknis

- Diuji lewat tinker: render `index()`, `show()`, `riwayat()` peserta, `exportPdf()`, `exportExcel()` dengan data rincian penuh (breakdown CAT/Wawancara/Presentasi + status terindikasi) — data uji sementara di jadwal & hasil real dikembalikan persis ke nilai semula setelah verifikasi

---

## Versi 1.17.0 - Ujikom Online: 2 Sesi CAT + Anti-Cheat + Nilai Manual + Perhitungan Berbobot
**Tanggal:** 13 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Modul Ujikom Online (CAT engine) diperluas untuk mendukung mode Paket Ujian "2 Sesi CAT" (v1.16.0): peserta mengerjakan Sesi Teknis lalu Sesi Mansoskul secara berurutan dengan timer masing-masing, nilai digabung dengan aspek Wawancara/Presentasi manual sesuai bobot jadwal, plus sistem anti-cheat (deteksi pindah tab/minimize/kamera mati, auto-submit di pelanggaran ke-3) dan halaman input nilai manual untuk Pusbin/Pewawancara. Alur ujian satu-sesi lama (mode Paket `acak_otomatis`/`manual`) **dipertahankan utuh** lewat percabangan kode, bukan ditimpa.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_13_000008_add_sesi_ganda_anticheat_to_ujikom_sesi.php` — kolom `sesi_teknis_id` (self-FK nullable) + `jenis_sesi` enum (`tunggal`/`teknis`/`mansoskul`, default `tunggal`) di `ujikom_sesi`. **Penyimpangan wajib dari draf**: unique constraint lama `(jadwal_id, peserta_id)` diperluas jadi `(jadwal_id, peserta_id, jenis_sesi)` — bukan cuma tambah kolom seperti draf — karena constraint lama akan menabrak begitu peserta punya 2 baris sesi (Teknis + Mansoskul) untuk jadwal yang sama
- **BARU** `database/migrations/2026_07_13_000009_add_nilai_diperoleh_to_ujikom_sesi_soal.php` — kolom `nilai_diperoleh` (skala 1-5, khusus soal Mansoskul)
- **BARU** `database/migrations/2026_07_13_000010_create_ujikom_nilai_manual_table.php` — tabel `ujikom_nilai_manual` (nilai Wawancara/Presentasi 1-5 per peserta per kompetensi)
- **BARU** `database/migrations/2026_07_13_000011_create_ujikom_pelanggaran_table.php` — tabel `ujikom_pelanggaran` (log anti-cheat)
- **BARU** `database/migrations/2026_07_13_000012_add_kompetensi_fields_to_ujikom_hasil.php` — `ujikom_hasil` tambah `nilai_teknis`, `nilai_mansoskul`, `bobot_teknis`, `bobot_mansoskul`, `status_kecurangan`

### Model
- **BARU** `app/Models/UjikomNilaiManual.php`, `app/Models/UjikomPelanggaran.php`
- **UBAH** `app/Models/UjikomSesi.php` — relasi `sesiTeknis()`/`sesiMansoskul()`/`pelanggaran()`, accessor `label_jenis_sesi`; **baru** `hitungNilaiSesi()` (nilai satu sesi saja, tanpa sync ke hasil), `hitungNilaiKompetensi()` (gabung CAT+Wawancara+Presentasi sesuai bobot aspek jadwal)
- **UBAH** `app/Models/UjikomHasil.php` — fillable/casts kolom baru
- **BUG DITEMUKAN & DIPERBAIKI**: `nilai_diperoleh` belum ada di `$fillable` model `UjikomSesiSoal` — nilai skala Mansoskul diam-diam tidak pernah tersimpan (silent mass-assignment drop, tidak ada error), ketahuan saat testing end-to-end

### Controller
- **ROMBAK SEBAGIAN** `app/Http/Controllers/UjikomOnlineController.php` (~15 method baru/diubah, alur lama dipertahankan via percabangan `jenis_sesi`/mode paket):
  - `mulai()`: percabangan ke `mulaiSesiTaksonomi()` (baru) kalau paket mode `sesi_taksonomi`, else alur lama utuh
  - `jawab()`: skoring benar/salah (Teknis) vs nilai skala 1-5 dari pilihan (Mansoskul)
  - `submit()`, `tutupSesi()`, `forceSubmit()`, `ujian()`: pakai helper baru `selesaikanSesi()` yang sadar jenis sesi (tunggal vs teknis/mansoskul)
  - **Baru**: `cobaFinalisasiHasil()` (gabung nilai kedua sesi + aspek manual sesuai bobot, tunda ke status `belum_dinilai` kalau nilai manual yang aktif belum lengkap), `hasilGabungan()`, `catatPelanggaran()` (AJAX anti-cheat, auto-submit di pelanggaran ke-3), `formNilaiManual()`, `inputNilaiManual()`
  - `bukaSesi()` di-guard menolak paket mode `sesi_taksonomi` (konsep "buka sesi massal" tidak relevan, sesi dibuat otomatis per-peserta)
  - `index()`: data 2-sesi per peserta (status Sesi 1/Sesi 2, link lanjut/hasil gabungan)
  - `monitoring()`: ringkasan jumlah & jenis pelanggaran per sesi

### Routes
- **UBAH** `routes/web.php` — 5 route baru: `hasil-gabungan`, `pelanggaran`, `nilai-manual.form`, `nilai-manual.store`

### View
- **BARU** `resources/views/ujikom/online/hasil_gabungan.blade.php`, `resources/views/ujikom/online/input_nilai_manual.blade.php`
- **UBAH** `resources/views/ujikom/online/ujian.blade.php` — label sesi ("Sesi 1: Teknis"/"Sesi 2: Mansoskul") + JS anti-cheat (Page Visibility API, `getUserMedia` pantau kamera tiap 5 detik, lapor via AJAX)
- **UBAH** `resources/views/ujikom/online/index.blade.php` — **(perluasan wajib di luar permintaan literal)** UI 2 sesi untuk peserta (badge status per sesi + tombol lanjut yang tepat) dan link "Nilai Manual" untuk admin — tanpa ini peserta tidak akan punya cara memicu alur 2 sesi sama sekali
- **UBAH** `resources/views/ujikom/online/monitoring.blade.php` — kolom "Log Pelanggaran" + badge "Terindikasi Kecurangan" (≥3 pelanggaran)

---

## Catatan Teknis

- Alur ujian mode lama (`acak_otomatis`/`manual`, sesi `jenis_sesi='tunggal'`) **tidak disentuh secara fungsional** — semua percabangan memastikan kode lama tetap berjalan identik, diverifikasi lewat regresi penuh
- Diuji end-to-end lewat tinker (bukan cuma render): alur 2 sesi penuh (mulai→jawab→submit Teknis→mulai lagi→jawab→submit Mansoskul→finalisasi otomatis), status "menunggu nilai manual" saat aspek aktif, `inputNilaiManual()` memicu ulang finalisasi, anti-cheat 3x pelanggaran→auto-submit, dan regresi alur lama — semua data uji dihapus/dikembalikan setelah verifikasi

---

## Versi 1.16.0 - Konfigurasi Aspek Penilaian Ujikom + Paket Ujian 2 Sesi CAT
**Tanggal:** 13 Juli 2026
**Status:** Selesai ✅ (fitur konfigurasi & komposisi soal — alur ujian 2-sesi sungguhan di Ujikom Online BELUM dibangun, lihat Catatan Teknis)

---

## Ringkasan

Dua penambahan pada modul Jadwal Ujikom dan Paket Ujian:

1. **Konfigurasi Aspek Penilaian di Jadwal Ujikom** — Pusbin sekarang bisa menentukan per jadwal ujian apakah Kompetensi Teknis dan Kompetensi Mansoskul menyertakan aspek Wawancara dan/atau Presentasi (selain Tes CAT yang selalu aktif otomatis), plus `jenjang_tujuan` ujian yang menentukan bobot Teknis vs Mansoskul (dari `config/bobot_penilaian_jft.php`).
2. **Paket Ujian mode baru "2 Sesi CAT"** — selain mode `acak_otomatis` dan `manual` yang sudah ada, Paket Ujian sekarang punya mode `sesi_taksonomi`: paket dibagi 2 sesi independen (Sesi Teknis by Kategori Soal, Sesi Mansoskul by Matra), masing-masing dengan Taksonomi Bloom maksimal dan jumlah soal sendiri. Komposisi jumlah soal per level taksonomi (C1–taksonomi maks) dihitung **otomatis berbobot berjenjang** (mis. maks C3 → rasio C1:C2:C3 = 1:2:3), dengan preview realtime via AJAX saat admin mengisi form.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_13_000005_add_aspek_penilaian_to_ujikom_jadwal.php` — 4 kolom boolean (`teknis_wawancara_aktif`, `teknis_presentasi_aktif`, `mansoskul_wawancara_aktif`, `mansoskul_presentasi_aktif`, default `false`) + `jenjang_tujuan` (string, nullable) di `ujikom_jadwal`
- **BARU** `database/migrations/2026_07_13_000006_add_sesi_taksonomi_to_paket_ujian.php`
  - Enum `paket_ujian.mode_pemilihan` diperluas `['acak_otomatis','manual']` → `+ 'sesi_taksonomi'` (raw SQL, additive — tidak perlu tahap migrasi data karena nilai lama tidak berubah)
  - 8 kolom baru nullable: `durasi_menit_teknis`, `jumlah_soal_teknis`, `taksonomi_maks_teknis` (enum C1–C6), `soal_kategori_id_teknis`, `durasi_menit_mansoskul`, `jumlah_soal_mansoskul`, `taksonomi_maks_mansoskul` (enum C1–C6), `matra_mansoskul` (enum darat/laut/udara/asdp/perkeretaapian)
- **BARU** `database/migrations/2026_07_13_000007_create_paket_ujian_komposisi_taksonomi_table.php` — tabel `paket_ujian_komposisi_taksonomi` (`paket_ujian_id`, `jenis_sesi` enum teknis/mansoskul, `taksonomi`, `jumlah_soal`)

### Config
- **BARU** `config/bobot_penilaian_jft.php` — bobot Teknis vs Mansoskul per jenjang (mis. Ahli Utama 65/35, Pemula 80/20), dibaca oleh `UjikomJadwal::getBobotJenjang()`

### Model
- **UBAH** `app/Models/UjikomJadwal.php` — fillable/casts field aspek penilaian baru; **baru** `getBobotAspek(string $kompetensi)` (hitung bobot CAT/Wawancara/Presentasi: CAT selalu 100% kalau keduanya nonaktif, 50/25/25 kalau keduanya aktif, dst — sesuai spesifikasi), `getBobotJenjang()` (baca config), accessor `label_jenjang_tujuan`
- **BARU** `app/Models/PaketUjianKomposisiTaksonomi.php` — model baris komposisi taksonomi per sesi, accessor `label_taksonomi`
- **UBAH** `app/Models/PaketUjian.php`
  - Relasi baru `komposisiTaksonomi()`, `kategoriTeknis()` (belongsTo `SoalKategori` via `soal_kategori_id_teknis`)
  - **Baru** `hitungKomposisiTaksonomi(string $taksonomiMaks, int $totalSoal)` — hitung komposisi per level taksonomi berbobot berjenjang (bobot 1..n, sisa pembulatan dibebankan ke taksonomi tertinggi)
  - **Baru** `generateKomposisiTaksonomi()` — hapus & generate ulang baris `paket_ujian_komposisi_taksonomi` dari konfigurasi sesi aktif
  - **Baru** `generateSoalSesi(string $jenisSesi)` — ambil soal aktif acak sesuai komposisi taksonomi + kategori/matra sesi
  - **Baru** `cekKetersediaanKomposisi()` — cek soal aktif tersedia per baris komposisi (dipakai `cekKetersediaanSoal()` untuk mode `sesi_taksonomi`, format detail sama persis dengan mode `acak_otomatis` jadi otomatis kompatibel dengan banner peringatan yang sudah ada)
  - `generateSoalUntukPeserta()` diperluas: mode `sesi_taksonomi` menggabungkan `generateSoalSesi('teknis')` + `generateSoalSesi('mansoskul')` (dipakai untuk preview admin — lihat Catatan Teknis soal batasan)
  - Label mode: `sesi_taksonomi` → "2 Sesi CAT"

### Controller
- **UBAH** `app/Http/Controllers/UjikomJadwalController.php` — `store()`/`update()`: validasi & simpan `jenjang_tujuan` (required) + 4 field aspek boolean
- **UBAH** `app/Http/Controllers/PaketUjianController.php`
  - `store()`/`update()`: validasi kondisional mode `sesi_taksonomi` (tiap sesi: kalau `jumlah_soal_*` diisi maka durasi/taksonomi_maks/kategori-matra wajib ikut terisi; minimal 1 sesi harus diisi); `durasi_menit` & `jumlah_soal` total di-auto-hitung dari jumlah kedua sesi; panggil `generateKomposisiTaksonomi()` setelah simpan
  - **Baru** `previewKomposisi(Request $request)` — endpoint AJAX, terima `jenis_sesi`/`taksonomi_maks`/`jumlah_soal`/`soal_kategori_id`/`matra`, kembalikan breakdown komposisi + ketersediaan soal aktif per taksonomi (dipakai form create/edit untuk preview realtime)
  - `show()`/`edit()`: load relasi `komposisiTaksonomi`, `kategoriTeknis`

### Routes
- **UBAH** `routes/web.php` — route baru `GET paket-ujian/preview-komposisi`, ditempatkan sebelum `/{id}`

### View
- **UBAH** `resources/views/ujikom/jadwal/create.blade.php`, `edit.blade.php` — card "Konfigurasi Aspek Penilaian": dropdown Jenjang Tujuan (wajib) + checkbox Wawancara/Presentasi per kompetensi (Teknis, Mansoskul)
- **UBAH** `resources/views/paket_ujian/create.blade.php`, `edit.blade.php` — radio mode ke-3 "2 Sesi CAT"; panel baru 2 kolom (Sesi Teknis: Kategori + Taksonomi Maks + Jumlah Soal + Durasi; Sesi Mansoskul: Matra + Taksonomi Maks + Jumlah Soal + Durasi), tiap sesi punya preview komposisi realtime (tabel butuh vs tersedia, baris merah kalau kurang) via AJAX ke `preview-komposisi`
- **UBAH** `resources/views/paket_ujian/show.blade.php` — kartu info per sesi + tabel komposisi taksonomi dengan status ketersediaan; tombol "Generate Preview" digunakan bersama utk mode `acak_otomatis` & `sesi_taksonomi`. **Perbaikan tambahan (di luar permintaan, wajib)**: kondisi tampilan kolom kiri/kanan sebelumnya hanya mengenal 2 mode (`manual` vs `else`=acak) — kalau tidak diperbaiki, paket mode `sesi_taksonomi` akan tampil kosong/membingungkan (iterasi `kategoriAcak` yang kosong)

---

## Catatan Teknis

- **Belum diimplementasikan**: alur ujian sungguhan 2-sesi-terpisah di Ujikom Online (peserta mengerjakan Sesi Teknis lalu Sesi Mansoskul dengan timer masing-masing, hasil skor per sesi digabung pakai bobot dari `getBobotJenjang()`) — ini di luar cakupan prompt saat ini. Saat ini `generateSoalUntukPeserta('sesi_taksonomi')` hanya menggabungkan kedua sesi jadi satu set soal untuk keperluan preview admin, BUKAN alur ujian bertahap yang sesungguhnya.
- `PaketUjianKategoriAcak.jenis_soal` (enum `umum`/`spesifik`, dipakai mode `acak_otomatis`) sengaja **tidak** diubah — konsep berbeda dari `bank_soal.jenis` (`mansoskul`/`teknis`) yang di-rename di v1.15.0; `BankSoal::umum()`/`spesifik()` scope alias tetap menjembatani keduanya
- Diuji lewat tinker: `hitungKomposisiTaksonomi()`/`generateKomposisiTaksonomi()` (2+4+6+8=20 sesuai bobot 1:2:3:4), `cekKetersediaanKomposisi()`, endpoint `previewKomposisi()`, `UjikomJadwalController::store()`, `PaketUjianController::store()`/`update()` end-to-end via simulasi Request, render semua view yang diubah tanpa error — data uji coba dihapus setelah verifikasi

---

## Versi 1.15.0 - Restrukturisasi Bank Soal & Kategori Bank Soal
**Tanggal:** 13 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Restrukturisasi klasifikasi Bank Soal: kolom `tingkat_kesulitan` (mudah/sedang/sulit) dihapus total; `bank_soal.jenis` di-rename dari `umum`/`spesifik` menjadi `mansoskul`/`teknis` (istilah baku); soal **Mansoskul** sekarang punya kolom `matra` sendiri (bukan lagi lewat Kategori Soal) dan pilihan jawabannya pakai **skala nilai 1–5** (bukan benar/salah); soal **Teknis** tetap pakai sistem benar/salah + Kategori Soal seperti sebelumnya. Kategori Soal sekarang murni khusus soal Teknis.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_13_000002_drop_tingkat_kesulitan_from_bank_soal.php` — hapus kolom `tingkat_kesulitan`
- **BARU** `database/migrations/2026_07_13_000003_update_jenis_add_matra_bank_soal.php` — rename nilai enum `jenis` (`umum`→`mansoskul`, `spesifik`→`teknis`, 3-tahap: perluas → migrasi data → persempit, raw SQL karena `doctrine/dbal` tidak terinstall); tambah kolom `matra` (enum darat/laut/udara/asdp/perkeretaapian, nullable, khusus Mansoskul)
- **BARU** `database/migrations/2026_07_13_000004_add_nilai_skala_to_bank_soal_pilihan.php` — tambah `nilai_skala` (tinyint 1-5, nullable) di `bank_soal_pilihan`

### Model
- **UBAH** `app/Models/BankSoal.php` — fillable: `tingkat_kesulitan` dihapus, `matra` ditambah; scope `umum()`/`spesifik()` dipertahankan sebagai **alias backward-compat** (internal diarahkan ke `jenis='mansoskul'`/`'teknis'`) karena `PaketUjian::generateSoalUntukPeserta()` masih memanggilnya; scope baru `mansoskul()`/`teknis()`; accessor `label_jenis`/`label_matra` baru, `label_tingkat`/`badge_tingkat` dihapus
- **UBAH** `app/Models/BankSoalPilihan.php` — fillable/casts tambah `nilai_skala`

### Controller
- **UBAH** `app/Http/Controllers/BankSoalController.php` — `store()`/`update()`: validasi & branching penuh per jenis (Teknis wajib `soal_kategori_id`+`jawaban_benar`, null `matra`; Mansoskul wajib `matra`+`nilai_pilihan_a/b/c/d` 1-5, null `soal_kategori_id`); `index()`: filter `matra`, banner peringatan soal Mansoskul yang belum diisi `matra`; `getByKategori()` disesuaikan istilah baru
- **PERBAIKAN TAMBAHAN (di luar permintaan, wajib)** `app/Http/Controllers/PaketUjianController.php` — modul Paket Ujian (soal-picker manual & preview generator) ternyata bergantung langsung pada `tingkat_kesulitan`/`label_tingkat`/`badge_tingkat`; kalau tidak diperbaiki akan error begitu kolom dihapus. Diganti ke basis Jenis (Mansoskul/Teknis): `show()`, `previewSoal()`, `getSoalByKategori()`

### Import/Export
- **UBAH** `app/Imports/BankSoalImport.php` — validasi ulang total per jenis (matra+nilai_pilihan untuk Mansoskul, kategori+jawaban_benar untuk Teknis)
- **UBAH** `app/Exports/BankSoalTemplateExport.php` — template 16 kolom (tambah `matra`, `nilai_pilihan_a/b/c/d`; hapus `tingkat_kesulitan`), contoh data Mansoskul + Teknis

### View
- **UBAH** `resources/views/bank_soal/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `import.blade.php` — form pakai toggle JS Jenis (radio jawaban benar utk Teknis vs 4× select Nilai Skala 1-5 utk Mansoskul); kolom Tingkat dihapus, kolom Kategori/Matra kondisional
- **UBAH** `resources/views/soal_kategori/index.blade.php` — catatan penjelasan bahwa kategori "Umum" sudah tidak relevan (Mansoskul kini pakai `matra` langsung); badge "Tidak Terpakai" utk kategori "Umum" tanpa soal Teknis terkait
- **PERBAIKAN TAMBAHAN (di luar permintaan, wajib)** `resources/views/paket_ujian/create.blade.php`, `edit.blade.php`, `show.blade.php` — soal-picker & preview diganti ke basis Jenis, konsisten dengan perbaikan controller di atas

---

## Catatan Teknis

- **Bug Blade pre-existing ditemukan & diperbaiki**: `@json(...)` berisi array literal multi-baris yang memuat pemanggilan fungsi (mis. `Str::limit(...)`) di dalam closure `fn() =>` menyebabkan Blade compiler memotong output PHP di tengah jalan (`ParseError: Unclosed '['`) — bug ini SUDAH ADA sebelum perubahan ini (di `paket_ujian/edit.blade.php`, preload `soalTerpilih`), baru ketahuan saat render-test. Diperbaiki dengan memindahkan pembentukan array dari Blade ke Controller (`PaketUjianController::edit()`), view tinggal `@json($variabel)`
- Diuji lewat tinker: migrasi + verifikasi skema, render semua view Bank Soal & Paket Ujian yang diubah (dengan `Auth::login()` + `View::share('errors', ...)` supaya representatif), sweep codebase utk sisa referensi `tingkat_kesulitan`/`label_tingkat`/`badge_tingkat`/nilai enum lama

---

## Versi 1.14.0 - Sederhanakan Pengangkatan JFT (Hapus Ranking)
**Tanggal:** 13 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Alur Pengangkatan JFT (v1.12.0) disederhanakan total: Pusbin tidak lagi melakukan seleksi/ranking kandidat. Admin Unit sekarang memilih sendiri peserta yang diusulkan (dari pegawai yang sudah lulus ujikom), divalidasi otomatis terhadap sisa formasi saat diinput — bukan diseleksi/di-ranking setelah diajukan. Pusbin tinggal generate surat rekomendasi dan konfirmasi TTD. Alur status dipotong dari 6 langkah (`draft → diajukan → diproses → disetujui → ditolak → selesai`) menjadi 4 (`draft → diajukan → menunggu_ttd → selesai`, + cabang `ditolak`).

Perubahan ini jauh lebih besar dari sekadar "hapus ranking" — form `create.blade.php` sebelumnya **sama sekali tidak punya UI pilih peserta** (kandidat 100% otomatis dari `generateKandidat()` berdasar hasil ujikom), jadi harus dibangun dari nol.

---

## Perubahan

### Database
- **BARU** `database/migrations/2026_07_13_000001_simplify_pengangkatan_status_and_ranking.php`
  - Enum `pengangkatan_permohonan.status`: `['draft','diajukan','diproses','disetujui','ditolak','selesai']` → `['draft','diajukan','menunggu_ttd','selesai','ditolak']` (raw SQL, `doctrine/dbal` tidak terinstall)
  - `pengangkatan_kandidat.ranking` diubah jadi nullable (sebelumnya NOT NULL tanpa default — instruksi asal set `ranking => null` akan gagal SQL tanpa perubahan ini, karena ranking tidak dipakai lagi)

### Model
- **UBAH** `app/Models/PengangkatanPermohonan.php`
  - `generateKandidat()` (ranking berdasar nilai) **dihapus total**
  - `getLabelStatusAttribute()`/`getBadgeStatusAttribute()` disesuaikan ke 5 status baru (`diproses`/`disetujui` dihapus, `menunggu_ttd` ditambah)

### Controller
- **ROMBAK** `app/Http/Controllers/PengangkatanController.php`
  - `proses()`, `setujui()`, `updateKandidat()` **dihapus**
  - **BARU** `getPesertaLulus(Request $request)` — AJAX, daftar pegawai lulus ujikom di suatu unit kerja yang belum pernah diusulkan di permohonan lain yang masih berjalan (dipakai picker "tambah peserta")
  - **BARU** `validasiFormasiPeserta(Request $request)` — AJAX, cek realtime apakah sisa formasi jabatan tujuan masih cukup untuk peserta ke-N yang mau ditambahkan
  - **BARU** `validasiPesertaGabungan()` (private) — validasi backend gabungan: sisa formasi per jabatan tujuan + tolak kalau ada peserta yang `ujikom_hasil_id`-nya sudah dipakai di permohonan lain (non-`ditolak`). Dipanggil dari `store()` dan `update()`
  - **BARU** `simpanPeserta()` (private) — simpan baris `PengangkatanKandidat` dari array peserta hasil form, semua otomatis `status_kandidat = 'direkomendasikan'`, `ranking = null`
  - `store()`/`update()`: kini menerima array `peserta[]` dari form (bukan cuma field permohonan dasar), validasi backend penuh sebelum simpan
  - `generateSurat()`: sekarang merangkap peran `setujui()` lama — transisi `diajukan → menunggu_ttd` sekaligus membuat record `PengangkatanSurat` (bisa dipanggil ulang untuk re-download tanpa duplikasi/transisi ulang)
  - `konfirmasiTtd()`: `menunggu_ttd → selesai` (sebelumnya dari `disetujui`)
  - `tolak()`: dipertahankan tapi disederhanakan — hanya dari status `diajukan` (lihat Catatan Teknis)

### Routes
- **UBAH** `routes/web.php` — route `proses`, `setujui`, `kandidat.update` dihapus; route baru `peserta-lulus` (GET) dan `validasi-formasi-peserta` (POST), static, ditempatkan sebelum `/{id}`

### View
- **DIBANGUN ULANG** `resources/views/pengangkatan/create.blade.php`
  - UI baru: cari & tambah peserta (select2 lokal dari hasil AJAX `peserta-lulus`), tabel peserta ditambahkan dengan status "Sesuai Formasi"/"Melebihi Formasi" per baris (AJAX `validasi-formasi-peserta` realtime), tombol hapus per baris, submit diblokir kalau ada baris tidak valid atau peserta kosong
  - Mode edit: peserta existing (`$permohonan->kandidat`) di-preload ke tabel yang sama
- **ROMBAK** `resources/views/pengangkatan/show.blade.php`
  - Panel "Verifikasi Ranking oleh Admin Pusbin" + tombol override status kandidat **dihapus**
  - Timeline stepper: 6 langkah → 4 langkah (`Draft → Diajukan → Menunggu TTD → Selesai`)
  - Panel aksi disederhanakan: `diajukan` → tombol "Generate Surat Rekomendasi" langsung (gabung proses+setujui lama) atau "Tolak"; `menunggu_ttd` → "Konfirmasi Surat Sudah Ditandatangani"
- **UBAH** `resources/views/pengangkatan/index.blade.php` — stat card & filter status disesuaikan 4 status baru; kolom "Direkomendasi" dihapus (di alur baru selalu identik dengan kolom "Peserta", jadi redundan)

---

## Catatan Teknis

- **Penyesuaian dari instruksi asal:** instruksi minta hapus `setujui()` **dan** `tolak()` sekaligus, tapi enum status baru tetap menyertakan `ditolak` — kalau `tolak()` benar-benar dihapus, status itu jadi tidak pernah bisa dicapai. `tolak()` dipertahankan (hanya dari status `diajukan`, tanpa keputusan per-kandidat) supaya `ditolak` tetap punya jalur masuk yang masuk akal.
- **Validasi anti-duplikat lintas permohonan** ditambahkan di luar permintaan eksplisit — ditemukan saat pengujian bahwa tanpa ini, satu pegawai (via `ujikom_hasil_id` yang sama) bisa diusulkan dobel di dua permohonan berbeda karena tidak ada unique constraint di level database untuk kombinasi ini.
- Query validasi formasi lebih sederhana dari contoh di instruksi: `pengangkatan_kandidat.jabatan_tujuan_id` sudah langsung menunjuk ke `formasi_jabatan.id` (bukan perlu kolom `jabatan_id` terpisah), jadi cukup `Formasijabatan::find($jabatanTujuanId)->sisa`.
- Diuji lewat tinker: `getPesertaLulus()` (exclude kandidat yang sudah terpakai), `validasiFormasiPeserta()` (skenario lolos & melebihi kuota), `validasiPesertaGabungan()` (tolak duplikat), dan render ketiga view (`create`/`show`/`index`) langsung tanpa error — semua pakai data dummy "Unit Kerja Dummy" dari sesi sebelumnya.

---

## Versi 1.13.0 - Rename Total "Rumah Sakit" → "Unit Kerja"
**Tanggal:** 9 Juli 2026
**Status:** Selesai ✅

---

## Ringkasan

Rename menyeluruh istilah "Rumah Sakit" (peninggalan project awal yang di-fork dari aplikasi rumah sakit) menjadi "Unit Kerja" di seluruh lapisan aplikasi: tabel database, primary key, kolom data, model, controller, dan variabel Blade. Dieksekusi 5 tahap bertahap dari paling aman ke paling berisiko (audit dulu → file mati → kolom data → tabel/PK/model → controller → variabel Blade), masing-masing diverifikasi penuh sebelum lanjut ke tahap berikutnya. Detail lengkap tiap tahap (termasuk hasil audit mentah, temuan di luar cakupan awal, dan alasan tiap keputusan) ada di `AUDIT_RUMAHSAKIT_2026-07-09.txt` di root project.

---

## Perubahan

### Tahap 1 — Audit
- Audit read-only menyeluruh: nama file/folder, semua occurrence di `app/`/`database/`/`routes/`/`resources/views/`, struktur tabel, foreign key, potensi konflik nama dengan modul "UnitKerja" yang sudah ada sebagian (ternyata tidak ada konflik — proses rename memang sudah pernah dimulai sebagian di masa lalu lewat nama route dan nama file generator, lalu berhenti)

### Tahap 2A — Bersih-bersih file mati (zero risk)
- Dipindahkan ke `_archive/` (bukan dihapus): `routes/web copy.php`, `resources/views/formasi_jabatan/Rumahsakit.php` (class duplikat mati), `resources/views/formasi_jabatan/bkp/` (4 file), folder `backup/` (~90 file), `rumahsakits.sql`

### Tahap 2B — Rename kolom data `nama_rumahsakit` → `nama_unit_kerja`
- **BARU** `database/migrations/2026_07_09_000001_rename_nama_rumahsakit_to_nama_unit_kerja.php` — raw SQL `RENAME COLUMN` (`doctrine/dbal` tidak terinstall)
- 16 file inti diubah (model, 13 controller, 2 command Excel generator — mapping alias header Excel sengaja dipertahankan)
- **Di luar cakupan awal, ikut diperbaiki:** `GenerateUnitKerjaSeederFromExcel.php` (akan diam-diam kehilangan data kalau tidak diperbaiki), `users/edit.blade.php`, dan 44 file blade lain yang menampilkan nama unit kerja dari relasi

### Tahap 2C — Rename tabel, primary key, dan model class (paling berisiko)
- **BARU** `database/migrations/2026_07_09_000002_rename_rumahsakits_to_unit_kerja.php` — `Schema::rename()` + raw SQL `CHANGE no_rs id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT`, PK & auto-increment terjaga
- `app/Models/Rumahsakit.php` → **dihapus**, jadi `app/Models/UnitKerja.php`
- 9 model dengan relasi `belongsTo(Rumahsakit::class, 'unit_kerja_id', 'no_rs')` → `belongsTo(UnitKerja::class, 'unit_kerja_id')`
- 13 controller + 3 command Excel generator disesuaikan (`no_rs`→`id`, `exists:rumahsakits,no_rs`→`exists:unit_kerja,id`, eager-load column list)
- **Di luar cakupan awal, ikut diperbaiki:** 20 file blade yang memakai `$u->no_rs` sebagai value dropdown "pilih unit kerja" — kalau dibiarkan, hampir semua form input di aplikasi akan mengirim value kosong
- 4 foreign key level database sungguhan (`sumber_daya_manusia`, `ujikom_pendaftaran`, `ujikom_permohonan`, `users` → `unit_kerja_id`) diverifikasi otomatis mengikuti rename tanpa putus (perilaku standar InnoDB)

### Tahap 2D — Rename controller
- `app/Http/Controllers/RumahsakitController.php` → **dihapus**, jadi `app/Http/Controllers/UnitKerjaController.php`
- `routes/web.php` disesuaikan — route name `unitkerja.*` tidak berubah (sudah benar dari sebelumnya)

### Tahap 2E — Rename variabel Blade
- 4 file CRUD inti (`users/index.blade.php`, `create.blade.php`, `show.blade.php`, `trash.blade.php`): `$rumahsakit(s)` → `$unitKerja(s)`, `$rs` → `$unitKerja`
- `UnitKerjaController.php`: `compact()` disesuaikan agar cocok dengan nama variabel baru di blade

---

## Catatan Teknis

- `doctrine/dbal` tidak terinstall — **semua** migration yang mengubah/rename kolom di rename ini pakai raw SQL (`DB::statement`), bukan `Schema::table(...)->renameColumn()`/`->change()`
- Sisa referensi "rumahsakit" yang **sengaja dipertahankan** (dilaporkan ke user, belum ditindaklanjuti): mapping alias header Excel di 2 command generator (`'nama_rumahsakit'`/`'no_rs'` sebagai kemungkinan nama kolom di file Excel yang diupload user — bukan referensi ke database), nama variabel lokal kosmetik (`$rumahsakitsQ` di `PetaDashboardController`), dan satu baris komentar mati di `users/index.blade.php`
- Data tidak hilang — jumlah unit kerja tetap **537** dari awal sampai akhir seluruh proses rename
- Setiap tahap diverifikasi lewat `php artisan route:list` (206 route, 0 error), `optimize:clear`, dan query fungsional langsung lewat tinker (relasi, validasi, raw join, route model binding, FK integrity)

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
