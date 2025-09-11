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
        Schema::table('detail_traksaksi', function (Blueprint $table) {
            $table->foreign(['id_transaksi'], 'detail_traksaksi_fk1')->references(['id_transaksi'])->on('tb_transaksi')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['id_limbah'], 'detail_traksaksi_fk3')->references(['id_limbah'])->on('tb_limbah')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_traksaksi', function (Blueprint $table) {
            $table->dropForeign('detail_traksaksi_fk1');
            $table->dropForeign('detail_traksaksi_fk3');
        });
    }
};
