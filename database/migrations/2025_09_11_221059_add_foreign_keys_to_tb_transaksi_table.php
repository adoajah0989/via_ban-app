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
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->foreign(['id_toko'], 'tb_transaksi_fk2')->references(['id_toko'])->on('tb_toko')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['id_pengepul'], 'tb_transaksi_fk6')->references(['id_pengepul'])->on('tb_pengepul')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->dropForeign('tb_transaksi_fk2');
            $table->dropForeign('tb_transaksi_fk6');
        });
    }
};
