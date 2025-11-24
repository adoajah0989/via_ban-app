<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->string('kode_transaksi', 8)
                ->nullable()
                ->after('id_transaksi')
                ->unique();
        });

        // Isi kode_transaksi untuk data lama dengan padding sederhana.
        DB::table('tb_transaksi')
            ->whereNull('kode_transaksi')
            ->orderBy('id_transaksi')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('tb_transaksi')
                        ->where('id_transaksi', $row->id_transaksi)
                        ->update([
                            'kode_transaksi' => str_pad((string) $row->id_transaksi, 8, '0', STR_PAD_LEFT),
                        ]);
                }
            }, 'id_transaksi');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->dropUnique(['kode_transaksi']);
            $table->dropColumn('kode_transaksi');
        });
    }
};

