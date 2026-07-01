<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Ujikom Sesi ────────────────────────────────────────────────────
        Schema::create('ujikom_sesi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_jadwal_id');
            $table->unsignedBigInteger('paket_ujian_id');
            $table->unsignedBigInteger('peserta_id');
            $table->enum('status_sesi', ['menunggu', 'berlangsung', 'selesai', 'timeout'])->default('menunggu');
            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->dateTime('batas_waktu')->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->unsignedInteger('jumlah_benar')->nullable();
            $table->unsignedInteger('jumlah_salah')->nullable();
            $table->unsignedInteger('jumlah_kosong')->nullable();
            $table->enum('status_lulus', ['lulus', 'tidak_lulus', 'belum_dinilai'])->default('belum_dinilai');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('ujikom_jadwal_id')->references('id')->on('ujikom_jadwal')->cascadeOnDelete();
            $table->foreign('paket_ujian_id')->references('id')->on('paket_ujian')->cascadeOnDelete();
            $table->foreign('peserta_id')->references('id')->on('ujikom_pendaftaran_peserta')->cascadeOnDelete();

            $table->unique(['ujikom_jadwal_id', 'peserta_id']);
        });

        // ─── Ujikom Sesi Soal ───────────────────────────────────────────────
        Schema::create('ujikom_sesi_soal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_sesi_id');
            $table->unsignedBigInteger('bank_soal_id');
            $table->unsignedInteger('urutan');
            $table->enum('pilihan_dipilih', ['A', 'B', 'C', 'D'])->nullable();
            $table->boolean('is_benar')->nullable();
            $table->dateTime('waktu_dijawab')->nullable();
            $table->timestamps();

            $table->foreign('ujikom_sesi_id')->references('id')->on('ujikom_sesi')->cascadeOnDelete();
            $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->cascadeOnDelete();

            $table->unique(['ujikom_sesi_id', 'bank_soal_id']);
        });

        // ─── Ujikom Sesi Log ────────────────────────────────────────────────
        Schema::create('ujikom_sesi_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_sesi_id');
            $table->enum('aksi', ['mulai', 'jawab', 'navigasi', 'submit', 'timeout', 'peringatan']);
            $table->json('detail')->nullable();
            $table->timestamps();

            $table->foreign('ujikom_sesi_id')->references('id')->on('ujikom_sesi')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_sesi_log');
        Schema::dropIfExists('ujikom_sesi_soal');
        Schema::dropIfExists('ujikom_sesi');
    }
};
