<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pemesanan')->onDelete('cascade'); // Merujuk ke tabel pemesanan
            $table->integer('rating'); // Rating (misalnya 1 sampai 5)
            $table->text('review'); // Ulasan pelanggan
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
