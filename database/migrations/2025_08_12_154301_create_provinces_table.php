<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('provinces', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('code', 2)->nullable()->unique(); // opsional (kode BPS)
            $t->string('name', 100);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('provinces');
    }
};
