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
        Schema::table('tb_toko', function (Blueprint $table) {
            $table->integer('id_pusat')->nullable()->after('id_toko');
        });

        Schema::table('tb_limbah', function (Blueprint $table) {
            $table->integer('id_pusat')->nullable()->after('id_limbah');
        });

        // Seed default pusat for existing data (Via Ban).
        $pusatId = DB::table('tb_pusat_toko')->insertGetId([
            'nama_pusat' => 'Via Ban',
            'kode_pusat' => 'VBN',
            'kontak' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach all existing stores and limbah to this pusat.
        DB::table('tb_toko')->update(['id_pusat' => $pusatId]);
        DB::table('tb_limbah')->update(['id_pusat' => $pusatId]);

        // Add foreign keys (nullable to avoid requiring DBAL to alter column).
        Schema::table('tb_toko', function (Blueprint $table) {
            $table->foreign('id_pusat', 'fk_toko_pusat')
                ->references('id_pusat')
                ->on('tb_pusat_toko')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('tb_limbah', function (Blueprint $table) {
            $table->foreign('id_pusat', 'fk_limbah_pusat')
                ->references('id_pusat')
                ->on('tb_pusat_toko')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_limbah', function (Blueprint $table) {
            $table->dropForeign('fk_limbah_pusat');
            $table->dropColumn('id_pusat');
        });

        Schema::table('tb_toko', function (Blueprint $table) {
            $table->dropForeign('fk_toko_pusat');
            $table->dropColumn('id_pusat');
        });
    }
};

