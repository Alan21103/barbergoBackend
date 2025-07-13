<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfoliosTable extends Migration
{
    public function up()
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained('users')->onDelete('cascade'); // Barber yang mengunggah portofolio
            $table->string('image_path'); // Lokasi gambar portofolio
            $table->string('description')->nullable(); // Deskripsi gambar portofolio
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('portfolios');
    }
}
