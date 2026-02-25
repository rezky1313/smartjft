<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdm_id')->constrained('sumber_daya_manusia')->cascadeOnDelete();
            $table->foreignId('jenjang_asal_id')->constrained('jenjang_jabatan');
            $table->foreignId('jenjang_target_id')->constrained('jenjang_jabatan');
            $table->enum('status', ['DRAFT','SUBMITTED','NEED_FIX','VERIFIED','APPLIED'])->default('DRAFT');
            $table->string('sk_number')->nullable();
            $table->string('sk_file_path')->nullable();
            $table->date('tmt_sk')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('promotions');
    }
};
