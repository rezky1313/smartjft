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
        Schema::create('pkr_referensi_pangkat', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('urutan')->unique(); // 1..17
            $table->string('golongan_ruang'); // "II/a", "III/b", dst
            $table->string('nama_pangkat');
            $table->enum('kategori', ['keterampilan', 'keahlian'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkr_referensi_pangkat');
    }
};
