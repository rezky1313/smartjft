<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel referensi rumus per JF (extensible untuk JF lain di masa depan)
        Schema::create('formula_rf_master', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jf'); // 'pkb' untuk sekarang, JF lain menyusul
            $table->enum('jenjang', ['pemula', 'terampil', 'mahir', 'penyelia']); // sesuaikan jika JF lain punya jenjang beda
            $table->string('unsur');
            $table->string('sub_unsur')->nullable();
            $table->text('butir_kegiatan');
            $table->string('satuan_hasil')->nullable();
            $table->decimal('angka_kredit', 10, 5)->nullable(); // referensi/dokumentasi, TIDAK dipakai kalkulasi
            $table->decimal('waktu_menit', 8, 2); // waktu penyelesaian butir kegiatan
            $table->enum('sumber_volume', [
                'kb_diuji_total', 'uji_pertama', 'uji_reguler',
                'bbm_bensin', 'bbm_solar', 'konstanta_hari_kerja'
            ])->nullable();
            $table->integer('urutan')->default(0); // urutan tampil sesuai Lampiran V asli
            $table->timestamps();

            $table->index(['kode_jf', 'jenjang']);
        });

        // Usulan formasi (induk)
        Schema::create('rekomendasi_formasi_usulan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jf')->default('pkb');
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja');
            $table->enum('jenis_instansi', ['kemenhub', 'dishub']); // menentukan alur data existing vs input manual
            $table->integer('tahun');
            $table->foreignId('diajukan_oleh')->constrained('users');
            $table->enum('status', [
                'draft', 'diajukan', 'menunggu_verifikasi', 'verifikasi_disepakati',
                'menunggu_ttd_ba', 'ba_selesai', 'menunggu_ttd_rekomendasi', 'selesai', 'ditolak'
            ])->default('draft');
            $table->timestamps();
        });

        // Input variabel (setara "Sheet1" di Excel referensi)
        Schema::create('rekomendasi_formasi_variabel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('rekomendasi_formasi_usulan')->onDelete('cascade');
            $table->integer('jumlah_kbwu')->nullable(); // Jumlah Kendaraan Bermotor Wajib Uji
            $table->integer('uji_pertama')->default(0);
            $table->integer('uji_reguler')->default(0);
            $table->integer('numpang_uji_masuk')->default(0);
            $table->integer('numpang_uji_keluar')->default(0);
            $table->integer('mutasi_masuk')->default(0);
            $table->integer('mutasi_keluar')->default(0);
            $table->integer('bbm_bensin')->default(0);
            $table->integer('bbm_solar')->default(0);
            $table->enum('hari_kerja', ['5', '6'])->default('5');
            $table->timestamps();
        });

        // Hasil kalkulasi per jenjang (4 baris per usulan)
        Schema::create('rekomendasi_formasi_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('rekomendasi_formasi_usulan')->onDelete('cascade');
            $table->enum('jenjang', ['pemula', 'terampil', 'mahir', 'penyelia']);
            $table->decimal('total_wpv', 12, 4); // ΣWpv hasil sistem
            $table->decimal('kebutuhan_raw', 10, 4); // sebelum pembulatan
            $table->integer('kebutuhan_bulat'); // setelah ROUNDUP
            $table->integer('bezetting')->default(0);
            $table->integer('formasi_sistem'); // kebutuhan_bulat - bezetting (hasil murni sistem)
            $table->integer('formasi_final')->nullable(); // bisa diedit manual Admin Pusbin, defaultnya = formasi_sistem
            $table->integer('usulan_admin_unit')->nullable(); // angka yang diusulkan sendiri oleh Admin Unit/Dishub (utk dibandingkan)
            $table->timestamps();
        });

        // Data pegawai yang diupload Dishub sebagai bagian usulan (untuk audit trail, terpisah dari tabel utama SDM)
        Schema::create('rekomendasi_formasi_pegawai_existing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('rekomendasi_formasi_usulan')->onDelete('cascade');
            $table->foreignId('sdm_id')->nullable()->constrained('sumber_daya_manusia'); // link ke record permanen setelah masuk sistem
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->enum('jenjang', ['pemula', 'terampil', 'mahir', 'penyelia']);
            $table->timestamps();
        });

        // Berita Acara & tanda tangan digital
        Schema::create('rekomendasi_formasi_berita_acara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('rekomendasi_formasi_usulan')->onDelete('cascade');
            $table->string('nomor_ba')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            // Tanda tangan digital pihak 1: Kepala Bidang Perencanaan & Pembentukan JFT (Pusbin)
            $table->foreignId('ttd_pusbin_oleh')->nullable()->constrained('users');
            $table->timestamp('ttd_pusbin_at')->nullable();
            $table->string('ttd_pusbin_ip')->nullable();
            // Tanda tangan digital pihak 2: Kepala Kantor/Dishub pengusul
            $table->foreignId('ttd_pengusul_oleh')->nullable()->constrained('users');
            $table->timestamp('ttd_pengusul_at')->nullable();
            $table->string('ttd_pengusul_ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_formasi_berita_acara');
        Schema::dropIfExists('rekomendasi_formasi_pegawai_existing');
        Schema::dropIfExists('rekomendasi_formasi_hasil');
        Schema::dropIfExists('rekomendasi_formasi_variabel');
        Schema::dropIfExists('rekomendasi_formasi_usulan');
        Schema::dropIfExists('formula_rf_master');
    }
};
