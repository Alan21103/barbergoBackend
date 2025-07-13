<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Mengubah nama kolom 'order_id' menjadi 'pemesanan_id'
            $table->renameColumn('order_id', 'pemesanan_id');

            // Menambahkan kolom deskripsi untuk ulasan
            $table->text('deskripsi')->nullable()->after('review');  // Menambahkan kolom deskripsi
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Membalikkan perubahan jika migration di-revert
            $table->renameColumn('pemesanan_id', 'order_id');
            $table->dropColumn('deskripsi');
        });
    }
}