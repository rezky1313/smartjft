<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->softDeletes(); // adds deleted_at
        });
    }
    public function down(): void {
        Schema::table('rumahsakits', function (Blueprint $t) {
            $t->dropSoftDeletes();
        });
    }
};
