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
            $table->foreign(['kode_wilayah'], 'fk_kode_limbah')->references(['kode_wilayah'])->on('tb_wilayah')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_toko', function (Blueprint $table) {
            $table->dropForeign('fk_kode_limbah');
        });
    }
};
