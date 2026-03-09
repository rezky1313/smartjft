# SMART JFT — Konteks Project untuk Claude Code

## Tentang Aplikasi

**SMART JFT** (Sistem Manajemen Adaptif, Responsif, Terintegrasi Jabatan Fungsional Transportasi)
adalah aplikasi web yang dikembangkan oleh **Pusat Pembinaan Jabatan Fungsional Transportasi (Pusbin JFT)**
Kementerian Perhubungan Republik Indonesia.

Aplikasi ini berfungsi sebagai **alat bantu tata kelola data dan informasi Jabatan Fungsional Transportasi (JFT)**
secara nasional — mulai dari unit kerja, formasi, hingga data individu pemangku JFT —
yang hasilnya tercermin dalam dashboard nasional, grafik, dan peta persebaran.

### Latar Belakang
- Terdapat sekitar **23.945 jabatan pelaksana** yang berpotensi bertransformasi menjadi JFT
- Dasar hukum: **Permenhub Nomor 4 Tahun 2025** tentang tugas dan fungsi Pusbin JFT
- Ada **19 tugas instansi pembina** JF yang menuntut tata kelola data yang rapi dan mutakhir
- Tanpa sistem terintegrasi, data tersebar di banyak file dan sulit dipantau secara nasional

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Backend Framework | Laravel (PHP) |
| Database | MySQL / MariaDB |
| Frontend Template | Blade (Laravel) |
| CSS Framework | Bootstrap |
| Peta Interaktif | Leaflet.js + OpenStreetMap |
| Tabel Interaktif | DataTables |
| Chart / Grafik | Chart.js atau library sejenis |
| Import Excel | Laravel Excel / Maatwebsite |
| Export | Excel & PDF |
| Server Lokal | Laragon (Pretty URL: smartjft.test) |
| Node.js | Vite (asset bundling) |

---

## Struktur Menu Aplikasi

### 1. Dashboard
Halaman utama yang menampilkan ringkasan data JFT secara nasional.

**Komponen:**
- **Ringkasan Nasional** — total pemangku JFT aktif (saat ini: 3.633)
- **Rekap JFT per Jenjang** — Pemula, Terampil, Mahir, Penyelia, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama
- **Filter Rekap** — filter by: Moda, Nama Formasi, Provinsi, Kabupaten/Kota
- **Tabel Rekap Terfilter** — daftar nama jabatan beserta jumlah per jenjang + total
- **Tombol Export** — Export Excel dan Export PDF
- **Grafik Piramida Pemangku JFT** — visualisasi distribusi per jenjang (horizontal bar chart)
- **Peta Persebaran** — peta interaktif Leaflet, setiap titik = satu unit kerja, marker berwarna per matra (Darat/Laut/Udara/Kereta)

### 2. Unit Kerja
Data seluruh unit kerja yang memiliki JFT.

**Kolom data:** No, Nama Unit Kerja, Provinsi, Kabupaten/Kota, Matra, Instansi, Latitude, Longitude, Actions (Edit/Delete)

**Fitur:**
- Tabel dengan fitur search dan pagination (DataTables)
- Tombol **+ Tambah Unit Kerja** — form dengan mini map Leaflet untuk klik/pilih koordinat
- Tombol **Sampah** — data yang dihapus (soft delete / trash)
- Edit dan Delete per baris

**Form Tambah Unit Kerja:**
- Nama Unit Kerja, Alamat, No. Telepon
- Dropdown: Provinsi, Kabupaten/Kota, Matra, Instansi
- Latitude & Longitude (otomatis terisi saat klik peta)
- Mini map Leaflet interaktif di sisi kanan form

### 3. Formasi
Data formasi JFT per unit kerja, terbagi 3 bagian: Kuota, Terisi (Eksisting), Sisa.

**Filter:** Provinsi, Kabupaten/Kota, Unit Kerja, Tahun

**Kolom per bagian (Kuota/Terisi/Sisa):**
Pemula, Terampil, Mahir, Penyelia, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama, TOTAL

**Fitur:**
- Data dikelompokkan per unit kerja / seksi
- Tombol **+ Tambah Formasi** — form tambah banyak formasi sekaligus (dinamis, bisa tambah baris)
- Tombol **+ Import Excel** — import formasi massal via file .xlsx/.xls
- Kolom **Terisi** dan **Sisa** otomatis berubah saat data Pegawai JFT diinput

**Form Tambah Formasi:**
- Pilih Unit Kerja + Tahun Formasi
- Tabel dinamis: Nama Formasi, Jenjang, Kuota (bisa tambah baris)

### 4. Pegawai JFT
Data profil individu pemangku Jabatan Fungsional Transportasi.

**Kolom data:** No, NIP, Nama, JK (Jenis Kelamin), Status, Pangkat/Gol, Jenjang, Unit Kerja, Provinsi, TMT, Masa Jabatan, Aktif, Aksi (Edit/Hapus)

**Fitur:**
- Tabel dengan fitur search dan pagination (DataTables)
- Badge **Aktif** berwarna hijau
- Tombol **+ Tambah Pemangku JFT** — form input manual
- Tombol **+ Import Excel** — import massal via .xlsx/.xls/.csv

**Form Tambah SDM:**
- NIP, NIK, Nama Lengkap, Jenis Kelamin
- Pendidikan Terakhir, Pangkat/Golongan, Status Kepegawaian (PNS/PPPK/CPNS/Non PNS)
- Formasi (opsional) — dropdown pilih formasi
- Unit Kerja (jika tanpa formasi)
- TMT Pengangkatan, Status Aktif

**Import Pegawai (SDM):**
- Format file: .xlsx, .xls, .csv
- Header penting: `jenis_kelamin` (L/P), `status_kepegawaian` (PNS/PPPK/CPNS/Non PNS)
- `unit_name + tahun + nama_formasi + level` untuk menghubungkan ke formasi
- Jika NIP ada → data di-update; jika kosong → dibuat baris baru

---

## Relasi Antar Data (Logika Bisnis)

```
Unit Kerja
    └── Formasi (kuota per jabatan per jenjang)
            └── Pegawai JFT (individu pemangku)
                    └── Dashboard (agregat nasional)
```

- Setiap **Pegawai JFT** terikat ke satu **Unit Kerja** dan satu **Formasi**
- Saat pegawai diinput → kolom **Terisi** di Formasi otomatis bertambah, **Sisa** berkurang
- **Dashboard** menampilkan agregat real-time dari semua data di bawahnya
- **Peta** menampilkan titik koordinat dari data **Unit Kerja**

---

## Data Master (Diinput Langsung via Database)

Data berikut bersifat relatif tetap dan tidak muncul di menu UI:
- Provinsi
- Kabupaten/Kota
- Jenjang jabatan (Pemula s.d. Ahli Utama)
- Nama jabatan fungsional

---

## Jenjang Jabatan Fungsional

| Kategori | Jenjang |
|---|---|
| Terampil | Pemula, Terampil, Mahir, Penyelia |
| Ahli | Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama |

---

## Nama Jabatan Fungsional yang Ada di Sistem

1. Penguji Kendaraan Bermotor
2. Pengawas Keselamatan Pelayaran
3. Teknisi Penerbangan
4. Asisten Inspektur Angkutan Udara
5. Inspektur Angkutan Udara
6. Asisten Inspektur Bandar Udara
7. Inspektur Bandar Udara
8. Asisten Inspektur Keamanan Penerbangan
9. Inspektur Keamanan Penerbangan
10. Asisten Inspektur Navigasi Penerbangan
11. Inspektur Navigasi Penerbangan
12. Asisten Inspektur Kelaikudaraan Pesawat Udara
13. Inspektur Kelaikudaraan Pesawat Udara
14. Asisten Inspektur Pengoperasian Pesawat Udara
15. Inspektur Pengoperasian Pesawat Udara
16. Penguji Sarana Perkeretaapian
17. Penguji Prasarana Perkeretaapian
18. Inspektur Sarana Perkeretaapian
19. Inspektur Prasarana Perkeretaapian
20. Auditor Perkeretaapian
21. Asisten Penguji Sarana Perkeretaapian
22. Asisten Penguji Prasarana Perkeretaapian

---

## Aturan & Konvensi Pengembangan

### Bahasa & UI
- Seluruh teks UI menggunakan **Bahasa Indonesia**
- Istilah teknis JFT mengikuti nomenklatur resmi Kementerian Perhubungan

### Coding
- Ikuti konvensi **Laravel** yang sudah ada di project
- Gunakan **Eloquent ORM** untuk query database, hindari raw SQL kecuali diperlukan
- Validasi input wajib ada di setiap form (server-side)
- Gunakan **soft delete** untuk data yang dihapus (ada fitur Sampah/Trash)

### Database
- **Jangan ubah struktur tabel utama** tanpa konfirmasi terlebih dahulu
- Jika perlu menambah kolom, gunakan **migration** baru
- Penamaan kolom menggunakan **snake_case**

### UI/UX
- Pertahankan tampilan dan tema yang sudah ada (sidebar gelap, Bootstrap)
- Tabel menggunakan **DataTables** (search, pagination, sorting sudah bawaan)
- Notifikasi/alert menggunakan style yang sudah ada di project
- Form input menggunakan komponen yang konsisten dengan halaman lain

---

## Aplikasi Terkait: SIJATI
## Catatan Integrasi

SMART JFT akan diintegrasikan dengan **SIJATI** (Sistem Jabatan Fungsional Transportasi), atau mungkin nantinya saya memasukkan fitur2 dari sijati ke SMART JFT ini
aplikasi pelayanan permohonan JFT yang mengelola:
- Permohonan Rekomendasi Formasi JF PKB
- Permohonan Uji Kompetensi
- Permohonan Pertimbangan Pengangkatan JF

**Visi integrasi:** Data yang diproses di SIJATI (surat pengangkatan, hasil uji kompetensi, dll.)

Untuk saat ini, 
fokus pengembangan hanya pada SMART JFT,
Abaikan semua hal terkait SIJATI sampai ada instruksi lebih lanjut.

---

## Catatan Penting untuk Claude

1. Selalu **tanyakan konfirmasi** sebelum mengubah struktur database atau migration
2. Jangan hapus atau overwrite fitur yang sudah berjalan tanpa instruksi eksplisit
3. Jika ada ambiguitas dalam permintaan, **tanyakan dulu** sebelum mengeksekusi
4. Prioritaskan **konsistensi UI** — ikuti pola halaman yang sudah ada
5. Setiap perubahan besar, **jelaskan dulu rencana perubahannya** sebelum dieksekusi
