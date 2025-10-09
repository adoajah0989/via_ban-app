<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_wilayah', function (Blueprint $table) {
            if (! $this->indexExists('tb_wilayah', 'tb_wilayah_kode_wilayah_unique')) {
                $table->unique('kode_wilayah', 'tb_wilayah_kode_wilayah_unique');
            }
            if (! $this->indexExists('tb_wilayah', 'tb_wilayah_nama_wilayah_unique')) {
                $table->unique('nama_wilayah', 'tb_wilayah_nama_wilayah_unique');
            }
        });

        Schema::table('tb_toko', function (Blueprint $table) {
            if (! $this->indexExists('tb_toko', 'tb_toko_kode_toko_unique')) {
                $table->unique('kode_toko', 'tb_toko_kode_toko_unique');
            }
        });

        Schema::table('tb_limbah', function (Blueprint $table) {
            if (! $this->indexExists('tb_limbah', 'tb_limbah_kode_limbah_unique')) {
                $table->unique('kode_limbah', 'tb_limbah_kode_limbah_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_wilayah', function (Blueprint $table) {
            if ($this->indexExists('tb_wilayah', 'tb_wilayah_kode_wilayah_unique')) {
                $table->dropUnique('tb_wilayah_kode_wilayah_unique');
            }
            if ($this->indexExists('tb_wilayah', 'tb_wilayah_nama_wilayah_unique')) {
                $table->dropUnique('tb_wilayah_nama_wilayah_unique');
            }
        });

        Schema::table('tb_toko', function (Blueprint $table) {
            if ($this->indexExists('tb_toko', 'tb_toko_kode_toko_unique')) {
                $table->dropUnique('tb_toko_kode_toko_unique');
            }
        });

        Schema::table('tb_limbah', function (Blueprint $table) {
            if ($this->indexExists('tb_limbah', 'tb_limbah_kode_limbah_unique')) {
                $table->dropUnique('tb_limbah_kode_limbah_unique');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        // Portable check for MySQL using information_schema
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT COUNT(1) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$database, $table, $index]
            );
            return ((int)($result[0]->cnt ?? 0)) > 0;
        } catch (\Throwable $e) {
            // Fallback to Schema::hasColumn-based coarse check (won't detect existing index)
            return false;
        }
    }
};

