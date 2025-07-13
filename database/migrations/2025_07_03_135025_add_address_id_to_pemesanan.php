<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressIdToPemesanan extends Migration
{
    public function up()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menambahkan address_id sebagai foreign key
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menghapus address_id jika migrasi dibatalkan
            $table->dropColumn('address_id');
        });
    }
}
