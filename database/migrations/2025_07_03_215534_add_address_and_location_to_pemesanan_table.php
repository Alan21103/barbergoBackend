<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_address_and_location_to_pemesanan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressAndLocationToPemesananTable extends Migration
{
    public function up()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menambahkan kolom alamat, latitude, dan longitude
            $table->string('alamat')->nullable();  // Menyimpan alamat pelanggan
            $table->decimal('latitude', 10, 7)->nullable();  // Menyimpan latitude
            $table->decimal('longitude', 10, 7)->nullable();  // Menyimpan longitude
        });
    }

    public function down()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menghapus kolom yang telah ditambahkan jika migration di-revert
            $table->dropColumn(['alamat', 'latitude', 'longitude']);
        });
    }
}
