<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 100);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uq_external_systems_company_code');
            $table->index(['company_id', 'is_active'], 'idx_external_systems_company_active');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_systems');
    }
};
