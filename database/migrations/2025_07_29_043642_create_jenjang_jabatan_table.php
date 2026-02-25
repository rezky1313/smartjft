<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateJenjangJabatanTable extends Migration
{
    public function up()
    {
        Schema::create('jenjang_jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jenjang');
            $table->string('golongan'); // contoh: II/a, III/b
            $table->enum('kategori', ['Terampil', 'Ahli']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jenjang_jabatan');
    }
}
