<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('regencies', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('province_id');
            $t->string('code', 4)->nullable()->unique(); // opsional
            $t->enum('type', ['KAB','KOTA']);
            $t->string('name', 120);
            $t->timestamps();

            $t->foreign('province_id')->references('id')->on('provinces')
              ->cascadeOnUpdate()->restrictOnDelete();
            $t->index(['province_id','type']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('regencies');
    }
};
