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
        Schema::create('auth_user_company_roles', function (Blueprint $table) {
           $table->id();

            $table->foreignId('user_company_access_id')
                ->constrained('auth_user_company_accesses')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('auth_roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_company_access_id', 'role_id'], 'uq_auth_user_company_roles');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_user_company_roles');
    }
};
