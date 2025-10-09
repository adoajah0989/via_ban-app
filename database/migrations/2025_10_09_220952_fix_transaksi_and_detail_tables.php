<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename typo table 'detail_traksaksi' to 'detail_transaksi' if needed
        if (Schema::hasTable('detail_traksaksi') && ! Schema::hasTable('detail_transaksi')) {
            Schema::rename('detail_traksaksi', 'detail_transaksi');
        }

        // Fix tb_transaksi columns: add status (lowercase) and timestamps
        if (Schema::hasTable('tb_transaksi')) {
            if (! Schema::hasColumn('tb_transaksi', 'status')) {
                Schema::table('tb_transaksi', function (Blueprint $table) {
                    $table->enum('status', ['pending', 'selesai', 'batal'])->default('pending')->after('sales');
                });

                // Copy data from old 'Status' column if it exists
                if (Schema::hasColumn('tb_transaksi', 'Status')) {
                    try {
                        DB::statement('UPDATE tb_transaksi SET status = Status WHERE Status IS NOT NULL');
                    } catch (\Throwable $e) {
                        // ignore, best effort
                    }
                }
            }

            // Add timestamps if missing
            Schema::table('tb_transaksi', function (Blueprint $table) {
                if (! Schema::hasColumn('tb_transaksi', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('tb_transaksi', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });

            // Drop old 'Status' column if present
            if (Schema::hasColumn('tb_transaksi', 'Status')) {
                Schema::table('tb_transaksi', function (Blueprint $table) {
                    $table->dropColumn('Status');
                });
            }
        }
    }

    public function down(): void
    {
        // Attempt to revert changes
        if (Schema::hasTable('detail_transaksi') && ! Schema::hasTable('detail_traksaksi')) {
            Schema::rename('detail_transaksi', 'detail_traksaksi');
        }

        if (Schema::hasTable('tb_transaksi')) {
            // Recreate 'Status' column if it does not exist
            if (! Schema::hasColumn('tb_transaksi', 'Status')) {
                Schema::table('tb_transaksi', function (Blueprint $table) {
                    $table->enum('Status', ['pending', 'selesai', 'batal'])->nullable();
                });
            }

            // Copy values back
            if (Schema::hasColumn('tb_transaksi', 'status')) {
                try {
                    DB::statement('UPDATE tb_transaksi SET Status = status WHERE status IS NOT NULL');
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // Drop lowercase status
            if (Schema::hasColumn('tb_transaksi', 'status')) {
                Schema::table('tb_transaksi', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }

            // Drop timestamps if present
            Schema::table('tb_transaksi', function (Blueprint $table) {
                if (Schema::hasColumn('tb_transaksi', 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn('tb_transaksi', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }
};

