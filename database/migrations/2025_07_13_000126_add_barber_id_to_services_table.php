<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Menambahkan kolom barber_id sebagai foreign key ke tabel users
            // constrained(): memastikan kolom ini merujuk ke id di tabel users
            // onDelete('cascade'): jika user dihapus, layanan terkait juga akan dihapus
            $table->foreignId('barber_id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            // Menghapus foreign key constraint terlebih dahulu
            $table->dropForeign(['barber_id']);
            // Kemudian menghapus kolom barber_id
            $table->dropColumn('barber_id');
        });
    }
};