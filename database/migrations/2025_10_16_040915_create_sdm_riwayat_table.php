<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('sdm_riwayat')) {
            Schema::create('sdm_riwayat', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sdm_id')->constrained('sumber_daya_manusia')->cascadeOnDelete();
                $table->foreignId('jenjang_id')->constrained('jenjang_jabatan');
                $table->foreignId('formasi_id')->nullable()->constrained('formasi_jabatan')->nullOnDelete();
                $table->date('tmt_mulai');
                $table->date('tmt_selesai')->nullable();
                $table->string('reason')->default('kenaikan_jenjang');
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('sdm_riwayat');
    }
};
