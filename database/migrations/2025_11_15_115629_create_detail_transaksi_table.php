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
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->integer('id_detail', true);
            $table->integer('id_transaksi')->index('detail_traksaksi_fk1');
            $table->integer('jumlah');
            $table->integer('id_limbah')->index('detail_traksaksi_fk3');
            $table->integer('harga_saat_transaksi');
            $table->integer('subtotal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
