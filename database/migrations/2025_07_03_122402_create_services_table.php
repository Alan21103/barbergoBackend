<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama layanan (misalnya 'Potong Rambut', 'Cukur Jenggot', dll)
            $table->text('description')->nullable(); // Deskripsi layanan
            $table->decimal('price', 8, 2); // Harga layanan
            $table->timestamps(); // Waktu dibuat dan diperbarui
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
}
