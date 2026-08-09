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
        Schema::create('pegawai_diklat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdm_id')->constrained('sumber_daya_manusia')->cascadeOnDelete();
            $table->string('nama_diklat');
            $table->string('penyelenggara');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai'); // >= tanggal_mulai, divalidasi di controller
            $table->enum('jenis_diklat', ['teknis', 'fungsional', 'kepemimpinan', 'lainnya']);
            $table->string('path_sertifikat'); // WAJIB diisi, divalidasi di controller (bukan nullable)
            $table->foreignId('input_by')->constrained('users');
            $table->timestamps();
            // TANPA soft delete -- konvensi tabel detail/transaksional lain di project
            // (ujikom_pendaftaran_berkas, pengangkatan_surat, pkr_angka_kredit_riwayat,
            // ujikom_hasil) semua tidak pakai soft delete, beda dari tabel master data.

            $table->index(['sdm_id', 'tanggal_mulai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_diklat');
    }
};
