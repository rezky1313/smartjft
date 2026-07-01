<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. paket_ujian
        Schema::create('paket_ujian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('ujikom_jadwal_id')->nullable()->constrained('ujikom_jadwal')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->integer('durasi_menit');
            $table->integer('passing_grade');
            $table->integer('jumlah_soal');
            $table->enum('mode_pemilihan', ['acak_otomatis', 'manual'])->default('acak_otomatis');
            $table->enum('status', ['draft', 'aktif', 'nonaktif'])->default('draft');
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_pilihan')->default(true);
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. paket_ujian_soal (mode manual)
        Schema::create('paket_ujian_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_ujian_id')->constrained('paket_ujian')->cascadeOnDelete();
            $table->foreignId('bank_soal_id')->constrained('bank_soal');
            $table->integer('urutan')->nullable();
            $table->timestamps();
            $table->unique(['paket_ujian_id', 'bank_soal_id']);
        });

        // 3. paket_ujian_kategori_acak (mode acak_otomatis)
        Schema::create('paket_ujian_kategori_acak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_ujian_id')->constrained('paket_ujian')->cascadeOnDelete();
            $table->foreignId('soal_kategori_id')->nullable()->constrained('soal_kategori')->nullOnDelete();
            $table->enum('jenis_soal', ['umum', 'spesifik']);
            $table->integer('jumlah_soal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_ujian_kategori_acak');
        Schema::dropIfExists('paket_ujian_soal');
        Schema::dropIfExists('paket_ujian');
    }
};
