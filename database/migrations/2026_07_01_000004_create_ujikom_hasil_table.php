<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujikom_hasil', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_jadwal_id');
            $table->unsignedBigInteger('ujikom_sesi_id')->nullable();
            $table->unsignedBigInteger('peserta_id');
            $table->enum('jenis_ujian', ['online', 'offline'])->default('offline');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->enum('status_kelulusan', ['lulus', 'tidak_lulus', 'belum_dinilai'])->default('belum_dinilai');
            $table->unsignedInteger('passing_grade')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->unsignedBigInteger('dinilai_oleh')->nullable();
            $table->date('tanggal_ujian')->nullable();
            $table->timestamps();

            $table->foreign('ujikom_jadwal_id')->references('id')->on('ujikom_jadwal')->cascadeOnDelete();
            $table->foreign('ujikom_sesi_id')->references('id')->on('ujikom_sesi')->nullOnDelete();
            $table->foreign('peserta_id')->references('id')->on('ujikom_pendaftaran_peserta')->cascadeOnDelete();
            $table->foreign('dinilai_oleh')->references('id')->on('users')->nullOnDelete();

            $table->unique(['ujikom_jadwal_id', 'peserta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_hasil');
    }
};
