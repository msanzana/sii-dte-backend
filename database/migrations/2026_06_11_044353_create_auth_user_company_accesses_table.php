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
        Schema::create('auth_user_company_accesses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('auth_users')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('can_select_company')->default(true);

            $table->timestamps();

            $table->unique(['user_id', 'company_id'], 'uq_auth_user_company_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_user_company_accesses');
    }
};
