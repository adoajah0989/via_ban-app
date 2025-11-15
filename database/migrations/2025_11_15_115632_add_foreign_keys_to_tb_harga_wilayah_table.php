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
        Schema::table('tb_harga_wilayah', function (Blueprint $table) {
            $table->foreign(['id_limbah'], 'fk_harga_limbah')->references(['id_limbah'])->on('tb_limbah')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['kode_wilayah'], 'fk_harga_wilayah')->references(['kode_wilayah'])->on('tb_wilayah')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_harga_wilayah', function (Blueprint $table) {
            $table->dropForeign('fk_harga_limbah');
            $table->dropForeign('fk_harga_wilayah');
        });
    }
};
