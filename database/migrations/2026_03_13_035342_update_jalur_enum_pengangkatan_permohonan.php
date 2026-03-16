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
        Schema::table('pengangkatan_permohonan', function (Blueprint $table) {
            $table->enum('jalur', [
                'pengangkatan_pertama',
                'inpasing',
                'kenaikan_jenjang',
                'promosi',
                'perpindahan_kategori',
                'perpindahan_jabatan',
                'pengangkatan_kembali'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengangkatan_permohonan', function (Blueprint $table) {
            $table->enum('jalur', [
                'inpasing',
                'promosi',
                'perpindahan_jabatan'
            ])->change();
        });
    }
};
