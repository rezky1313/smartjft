<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_ujian_komposisi_taksonomi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_ujian_id')->constrained('paket_ujian')->onDelete('cascade');
            $table->enum('jenis_sesi', ['teknis', 'mansoskul']);
            $table->enum('taksonomi', ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta']);
            $table->integer('jumlah_soal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_ujian_komposisi_taksonomi');
    }
};
