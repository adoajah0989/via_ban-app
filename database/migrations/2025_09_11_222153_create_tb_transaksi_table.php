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
        Schema::create('tb_transaksi', function (Blueprint $table) {
            $table->integer('id_transaksi', true);
            $table->date('tanggal');
            $table->integer('id_toko')->index('tb_transaksi_fk2');
            $table->integer('total_pickup');
            $table->decimal('sales', 10, 0);
            $table->enum('Status', ['pending', 'selesai', 'batal']);
            $table->integer('id_pengepul')->nullable()->index('tb_transaksi_fk6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_transaksi');
    }
};
