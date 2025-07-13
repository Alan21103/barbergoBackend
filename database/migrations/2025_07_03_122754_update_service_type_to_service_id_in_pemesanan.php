<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateServiceTypeToServiceIdInPemesanan extends Migration
{
    public function up()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menghapus kolom service_type
            $table->dropColumn('service_type');
            
            // Menambahkan kolom service_id yang merujuk ke tabel services
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Mengembalikan kolom service_type jika migrasi dibatalkan
            $table->string('service_type');
            
            // Menghapus kolom service_id
            $table->dropColumn('service_id');
        });
    }
}
