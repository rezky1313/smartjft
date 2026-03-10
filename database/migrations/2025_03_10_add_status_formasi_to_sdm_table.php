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
        Schema::table('sumber_daya_manusia', function (Blueprint $table) {
            // Tambahkan kolom status_formasi setelah formasi_jabatan_id
            $table->enum('status_formasi', ['terpenuhi', 'di_luar_formasi'])
                  ->default('terpenuhi')
                  ->after('formasi_jabatan_id')
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sumber_daya_manusia', function (Blueprint $table) {
            $table->dropColumn('status_formasi');
        });
    }
};
