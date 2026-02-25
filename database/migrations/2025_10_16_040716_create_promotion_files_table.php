<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('promotion_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->enum('kind', ['sk_terakhir','skp','sertifikat']);
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('promotion_files');
    }
};
