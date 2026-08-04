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
        // Mirroring pengangkatan_surat -- surat rekomendasi resmi (Bagian 5,
        // RF-1C). Nomor surat resmi TIDAK di-generate otomatis (sama seperti
        // pengangkatan_surat), diisi lewat proses persuratan/arsip institusi
        // di luar sistem; TTD Kapusbin JFT juga masih manual/fisik.
        Schema::create('rekomendasi_formasi_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('rekomendasi_formasi_usulan')->onDelete('cascade');
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->boolean('ditandatangani')->default(false);
            $table->timestamps();

            $table->unique('usulan_id'); // satu surat rekomendasi per usulan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_formasi_surat');
    }
};
