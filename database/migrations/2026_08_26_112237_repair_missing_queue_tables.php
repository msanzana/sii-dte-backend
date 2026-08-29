<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repara las tablas de infraestructura de colas que puedan
     * faltar en instalaciones existentes.
     *
     * La migración histórica create_jobs_table ya figura como
     * ejecutada en algunas bases, aunque la tabla jobs haya sido
     * eliminada posteriormente.
     */
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    /**
     * Esta es una migración correctiva.
     *
     * No eliminamos las tablas automáticamente durante rollback
     * porque en otras instalaciones podrían haber existido antes
     * de ejecutar esta reparación.
     */
    public function down(): void
    {
        //
    }
};