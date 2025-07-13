<?php

// database/migrations/xxxx_xx_xx_xxxxxx_reorder_columns_in_pemesanan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReorderColumnsInPemesananTable extends Migration
{
    public function up()
    {
        // Drop tabel pemesanan
        Schema::dropIfExists('pemesanan');

        // Membuat ulang tabel pemesanan dengan kolom urut yang benar
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id();  // Menambahkan id di depan
            $table->foreignId('pelanggan_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('barber_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->dateTime('scheduled_time');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
            $table->string('alamat');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Drop tabel pemesanan jika rollback
        Schema::dropIfExists('pemesanan');
    }
}
