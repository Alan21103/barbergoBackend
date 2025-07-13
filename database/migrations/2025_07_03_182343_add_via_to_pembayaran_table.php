<?php

// database/migrations/xxxx_xx_xx_add_via_to_pembayaran_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViaToPembayaranTable extends Migration
{
    public function up()
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->enum('via', ['cash', 'transfer', 'qris'])->default('cash'); // Menambahkan metode pembayaran
        });
    }

    public function down()
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('via');
        });
    }
}
