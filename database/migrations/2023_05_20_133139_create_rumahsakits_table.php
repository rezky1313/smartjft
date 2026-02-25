<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rumahsakits', function (Blueprint $table) {
            // $table->increments('no_rs');
            $table->id('no_rs')->autoIncrement();
            $table->string('nama_rumahsakit');
            $table->string('alamat');
            $table->string('no_telp');
            $table->string('jam_kerja');
            $table->string('fasilitas');
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rumahsakits');
    }
};
