<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('users')->onDelete('cascade'); // Relasi dengan pelanggan
            $table->string('address'); // Alamat pelanggan
            $table->string('city'); // Kota
            $table->string('postal_code'); // Kode Pos
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
