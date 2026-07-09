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
        Schema::create('formasi_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja')->onDelete('cascade');
            $table->string('nama_formasi');
            $table->string('jenjang');
            $table->string('kota_kabupaten');
            $table->string('kuota');
            $table->string('tahun_formasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formasi_jabatan');
    }
};
