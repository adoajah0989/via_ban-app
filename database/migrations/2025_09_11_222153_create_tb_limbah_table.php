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
        Schema::create('tb_limbah', function (Blueprint $table) {
            $table->integer('id_limbah', true);
            $table->string('nama_limbah');
            $table->integer('harga');
            $table->string('kode_limbah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_limbah');
    }
};
