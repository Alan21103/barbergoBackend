<?php

// database/migrations/xxxx_xx_xx_create_pembayaran_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePembayaranTable extends Migration
{
    public function up()
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemesanan_id')->constrained('pemesanan')->onDelete('cascade'); // Relasi dengan tabel pemesanan
            $table->decimal('amount', 10, 2); // Jumlah yang dibayar
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending'); // Status pembayaran
            $table->timestamp('paid_at')->nullable(); // Waktu pembayaran (nullable karena bisa saja belum dibayar)
            $table->timestamps(); // Timestamps untuk created_at dan updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('pembayaran');
    }
}

