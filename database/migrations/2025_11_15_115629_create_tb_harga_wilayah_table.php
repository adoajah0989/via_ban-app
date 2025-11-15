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
        Schema::create('tb_harga_wilayah', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_limbah')->index('fk_harga_limbah');
            $table->string('kode_wilayah', 10)->nullable()->index('kode_wilayah');
            $table->integer('harga');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_harga_wilayah');
    }
};
