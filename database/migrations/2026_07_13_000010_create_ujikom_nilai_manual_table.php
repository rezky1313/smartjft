<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujikom_nilai_manual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujikom_jadwal_id')->constrained('ujikom_jadwal')->cascadeOnDelete();
            $table->foreignId('peserta_id')->constrained('ujikom_pendaftaran_peserta')->cascadeOnDelete();
            $table->enum('kompetensi', ['teknis', 'mansoskul']);
            $table->enum('aspek', ['wawancara', 'presentasi']);
            $table->tinyInteger('nilai'); // 1-5
            $table->text('catatan')->nullable();
            $table->foreignId('dinilai_oleh')->constrained('users');
            $table->timestamps();
            $table->unique(['ujikom_jadwal_id', 'peserta_id', 'kompetensi', 'aspek'], 'uq_nilai_manual');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_nilai_manual');
    }
};
