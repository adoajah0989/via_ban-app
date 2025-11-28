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
        Schema::create('tb_laporan_pengepul', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('id_pengepul')->index();
            $table->date('bulan'); // disimpan sebagai tanggal awal bulan
            $table->string('format', 10)->default('pdf');
            $table->string('path')->nullable(); // path relatif pada disk "local"
            $table->decimal('grand_total', 15, 0)->default(0);
            $table->timestamps();

            $table->foreign('id_pengepul', 'fk_laporan_pengepul')
                ->references('id_pengepul')
                ->on('tb_pengepul')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_laporan_pengepul', function (Blueprint $table) {
            $table->dropForeign('fk_laporan_pengepul');
        });

        Schema::dropIfExists('tb_laporan_pengepul');
    }
};

