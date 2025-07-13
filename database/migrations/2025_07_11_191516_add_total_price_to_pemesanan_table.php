<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menambahkan kolom 'total_price' setelah kolom 'ongkir'
            // Menggunakan tipe data decimal untuk harga (presisi 10 digit total, 2 digit di belakang koma)
            // nullable() agar bisa null jika ada data lama yang belum memiliki total_price,
            // atau remove nullable() jika Anda ingin ini selalu terisi.
            $table->decimal('total_price', 10, 2)->after('ongkir')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            // Menghapus kolom 'total_price' jika migrasi di-rollback
            $table->dropColumn('total_price');
        });
    }
};
