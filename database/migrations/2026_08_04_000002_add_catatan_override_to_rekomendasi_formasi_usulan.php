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
        Schema::table('rekomendasi_formasi_usulan', function (Blueprint $table) {
            // Catatan alasan saat Admin Pusbin mengembalikan status usulan ke
            // 'draft' secara manual (kasus khusus, RF-1C Bagian 2) -- kunci
            // audit sederhana kenapa data yang sudah diverifikasi dibuka lagi
            // untuk diedit. Nullable karena hanya terisi kalau override pernah
            // terjadi.
            $table->text('catatan_override')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekomendasi_formasi_usulan', function (Blueprint $table) {
            $table->dropColumn('catatan_override');
        });
    }
};
