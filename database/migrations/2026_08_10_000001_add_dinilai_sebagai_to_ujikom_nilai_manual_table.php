<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_nilai_manual', function (Blueprint $table) {
            // Nullable: baris lama & input oleh admin (bukan pewawancara/penguji) tidak punya gelar ini.
            $table->enum('dinilai_sebagai', ['pewawancara', 'penguji'])->nullable()->after('dinilai_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_nilai_manual', function (Blueprint $table) {
            $table->dropColumn('dinilai_sebagai');
        });
    }
};
