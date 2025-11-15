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
            $table->integer('total_pickup')->nullable();
            $table->decimal('sales', 10, 0);
            $table->integer('id_pengepul')->nullable()->index('tb_transaksi_fk6');
            $table->string('kode_wilayah', 10)->nullable()->index('fk_transaksi_wilayah');
            $table->string('status', 10);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->integer('total_transaksi')->default(0);
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
