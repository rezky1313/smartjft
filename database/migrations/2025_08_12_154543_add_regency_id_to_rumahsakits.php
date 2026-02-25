<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->unsignedBigInteger('regency_id')->nullable()->after('no_rs');
            $t->foreign('regency_id')->references('id')->on('regencies')
              ->cascadeOnUpdate()->restrictOnDelete();
            $t->index('regency_id');
        });
    }
    public function down(): void {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->dropForeign(['regency_id']);
            $t->dropColumn('regency_id');
        });
    }
};
