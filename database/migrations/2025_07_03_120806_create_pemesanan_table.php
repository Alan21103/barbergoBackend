<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePemesananTable extends Migration
{
    public function up()
    {
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id();
            // Foreign key untuk customer (pelanggan) yang membuat pemesanan
            $table->foreignId('pelanggan_id')->constrained('users')->onDelete('cascade'); 
            // Foreign key untuk barber (tukang cukur) yang menerima pemesanan
            $table->foreignId('barber_id')->constrained('users')->onDelete('cascade');
            $table->string('service_type'); // Jenis layanan yang dipesan (misalnya potong rambut, cukur jenggot)
            $table->dateTime('scheduled_time'); // Waktu yang dijadwalkan
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']); // Status pemesanan
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pemesanan');
    }
}
