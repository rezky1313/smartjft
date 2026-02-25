<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->string('matra', 10)->nullable()->after('regency_id');   // Darat|Laut|Udara|Kereta
            $t->string('instansi', 10)->nullable()->after('matra');     // Pusat|Daerah
        });
    }

    public function down(): void
    {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->dropColumn(['matra','instansi']);
        });
    }
};
