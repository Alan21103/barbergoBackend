<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_ongkir_to_pemesanan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOngkirToPemesananTable extends Migration
{
    public function up()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->decimal('ongkir', 10, 2)->nullable(); // Menambahkan kolom ongkir
        });
    }

    public function down()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dropColumn('ongkir'); // Menghapus kolom ongkir jika rollback
        });
    }
}

