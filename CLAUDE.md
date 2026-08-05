# SMART JFT — Konteks Project untuk Claude Code

## Tentang Aplikasi

**SMART JFT** (Sistem Manajemen Adaptif, Responsif, Terintegrasi Jabatan Fungsional Transportasi)
adalah aplikasi web yang dikembangkan oleh **Pusat Pembinaan Jabatan Fungsional Transportasi (Pusbin JFT)**
Kementerian Perhubungan Republik Indonesia.

Aplikasi ini berfungsi sebagai **alat bantu tata kelola data dan informasi Jabatan Fungsional Transportasi (JFT)**
secara nasional — mulai dari unit kerja, formasi, hingga data individu pemangku JFT  —
yang hasilnya tercermin dalam dashboard nasional, grafik, dan peta persebaran.

### Latar Belakang
- Terdapat sekitar **23.945 jabatan pelaksana** yang berpotensi bertransformasi menjadi JFT
- Dasar hukum: **Permenhub Nomor 4 Tahun 2025** tentang tugas dan fungsi Pusbin JFT
- Ada **19 tugas instansi pembina** JF yang menuntut tata kelola data yang rapi dan mutakhir
- Tanpa sistem terintegrasi, data tersebar di banyak file dan sulit dipantau secara nasional

---

## Tech Stack

Server lokal: Laragon, pretty URL `smartjft.test`. (Framework & library lain: lihat `composer.json`/`package.json`/`public/library`.)

---

## Struktur Menu Aplikasi

Menu: Dashboard, Unit Kerja, Formasi, Pegawai JFT, Manajemen User (lihat `resources/views/` per modul untuk kolom/form/fitur — semua derivable dari kode).

### Unit Kerja
Data seluruh unit kerja yang memiliki JFT. pada kode, unit kerja dinamakan rumahsakit, dikarenakan saya menggunakan project awal ini dengan mengedit dari project rumah sakit, makanya hal2 yang dinamakan rumahsakit di sini maksudnya adalah unitkerja

### Pegawai JFT

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

## Halaman Belum Selesai — ABAIKAN Sementara

Terdapat 2 halaman yang sudah punya file (Controller, View, Model, Routes) 
di project tapi **belum selesai, tidak ditampilkan di menu, dan akan dirombak total** 
di masa mendatang. 

**Untuk saat ini: ABAIKAN sepenuhnya kedua halaman ini.**
Jangan sentuh, jangan edit, jangan hapus file-filenya.

| Halaman | Status | Instruksi |
|---|---|---|
| Promosi Jabatan | Belum selesai, tidak aktif | ⛔ Abaikan |
| Kompetensi Pemangku JFT | Belum selesai, tidak aktif | ⛔ Abaikan |

**Fokus pengembangan saat ini hanya pada:**
Dashboard, Unit Kerja, Formasi, Pegawai JFT, dan Manajemen User.

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
6. setiap ada perubahan atau penambahan fitur, dokumentasikan dengan detail, mengupdate file changelog.md pada folder root dan memory sertakan tanggal
7. untuk setiap session chat baru di awal selalu baca file claude.md dan changelog.md pada root folder untuk mengetahui konteks dan perubahan
8. **JANGAN tambahkan `Co-Authored-By: Claude` di commit message.** User tidak ingin Claude muncul sebagai co-author di GitHub.
