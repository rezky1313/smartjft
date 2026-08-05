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
        // Referensi koefisien angka kredit tahunan per jenjang (PerBKN 3/2023)
        Schema::create('pkr_referensi_koefisien', function (Blueprint $table) {
            $table->id();
            $table->string('jenjang'); // short-code: pemula, terampil, mahir, penyelia, ahli_pertama, ahli_muda, ahli_madya, ahli_utama
            $table->decimal('koefisien_tahunan', 6, 2);
            $table->timestamps();
        });

        // Referensi persentase angka kredit per predikat kinerja (SKP)
        Schema::create('pkr_referensi_predikat', function (Blueprint $table) {
            $table->id();
            $table->enum('predikat', ['sangat_baik', 'baik', 'cukup', 'kurang', 'sangat_kurang']);
            $table->decimal('persentase', 5, 2);
            $table->timestamps();
        });

        // Ambang batas AK kumulatif minimal untuk naik jenjang
        Schema::create('pkr_ambang_batas_jenjang', function (Blueprint $table) {
            $table->id();
            $table->string('kategori'); // keterampilan / keahlian
            $table->string('dari_jenjang');
            $table->string('ke_jenjang');
            $table->decimal('ak_kumulatif_minimal', 8, 2);
            $table->timestamps();
        });

        // Ledger riwayat angka kredit per pegawai per periode penilaian.
        // persentase_predikat & koefisien_tahunan disimpan di sini (bukan di-lookup ulang
        // dari tabel referensi) supaya riwayat lama tidak berubah kalau referensi diedit nanti.
        Schema::create('pkr_angka_kredit_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdm_id')->constrained('sumber_daya_manusia')->onDelete('cascade');
            $table->integer('tahun');
            $table->string('periode_bulan');
            $table->integer('jumlah_bulan');
            $table->enum('predikat_kinerja', ['sangat_baik', 'baik', 'cukup', 'kurang', 'sangat_kurang']);
            $table->decimal('persentase_predikat', 5, 2);
            $table->decimal('koefisien_tahunan', 6, 2);
            $table->decimal('angka_kredit_diperoleh', 10, 4);
            $table->string('jenjang_saat_itu');
            $table->text('catatan')->nullable();
            $table->foreignId('dinilai_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkr_angka_kredit_riwayat');
        Schema::dropIfExists('pkr_ambang_batas_jenjang');
        Schema::dropIfExists('pkr_referensi_predikat');
        Schema::dropIfExists('pkr_referensi_koefisien');
    }
};
