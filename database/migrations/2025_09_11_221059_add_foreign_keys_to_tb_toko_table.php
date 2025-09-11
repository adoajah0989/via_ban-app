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
        Schema::table('tb_toko', function (Blueprint $table) {
            $table->foreign(['kode_wilayah'], 'tb_toko_fk4')->references(['kode_wilayah'])->on('tb_wilayah')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_toko', function (Blueprint $table) {
            $table->dropForeign('tb_toko_fk4');
        });
    }
};
