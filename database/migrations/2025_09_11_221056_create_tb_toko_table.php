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
        Schema::create('tb_toko', function (Blueprint $table) {
            $table->integer('id_toko', true);
            $table->string('nama_toko');
            $table->string('kode_toko');
            $table->string('alamat');
            $table->string('kode_wilayah')->index('tb_toko_fk4');
            $table->string('nomor_telepon', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_toko');
    }
};
