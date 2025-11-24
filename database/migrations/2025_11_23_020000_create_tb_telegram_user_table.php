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
        Schema::create('tb_telegram_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('phone')->nullable();
            $table->enum('role', ['admin', 'pengepul']);
            $table->integer('id_pengepul')->nullable()->index();
            $table->unsignedBigInteger('id_user')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_pengepul', 'fk_telegram_pengepul')
                ->references('id_pengepul')
                ->on('tb_pengepul')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_user', 'fk_telegram_user_admin')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_telegram_user', function (Blueprint $table) {
            $table->dropForeign('fk_telegram_pengepul');
            $table->dropForeign('fk_telegram_user_admin');
        });

        Schema::dropIfExists('tb_telegram_user');
    }
};

