<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJadwalTable extends Migration
{
    public function up()
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained('users')->onDelete('cascade'); // Barber yang mengatur jadwal
            $table->dateTime('tersedia_dari'); // Waktu mulai tersedia
            $table->dateTime('tersedia_hingga'); // Waktu selesai tersedia
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jadwal');
    }
}
