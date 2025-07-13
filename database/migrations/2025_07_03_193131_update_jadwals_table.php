<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateJadwalsTable extends Migration
{
    public function up()
    {
        Schema::table('jadwal', function (Blueprint $table) {
            // Menambahkan kolom hari untuk menyimpan nama hari
            $table->string('hari')->after('barber_id');  // Kolom string untuk hari

            // Mengubah kolom tersedia_dari dan tersedia_hingga menjadi tipe 'time'
            $table->time('tersedia_dari')->change();
            $table->time('tersedia_hingga')->change();
        });
    }

    public function down()
    {
        Schema::table('jadwals', function (Blueprint $table) {
            // Menghapus kolom 'hari' jika rollback
            $table->dropColumn('hari');

            // Mengembalikan kolom 'tersedia_dari' dan 'tersedia_hingga' ke tipe datetime
            $table->dateTime('tersedia_dari')->change();
            $table->dateTime('tersedia_hingga')->change();
        });
    }
}