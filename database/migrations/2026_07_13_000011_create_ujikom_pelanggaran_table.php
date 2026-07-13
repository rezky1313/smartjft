<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujikom_pelanggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujikom_sesi_id')->constrained('ujikom_sesi')->onDelete('cascade');
            $table->enum('jenis_pelanggaran', ['pindah_tab', 'minimize', 'kamera_mati']);
            $table->integer('pelanggaran_ke');
            $table->timestamp('waktu_kejadian');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_pelanggaran');
    }
};
