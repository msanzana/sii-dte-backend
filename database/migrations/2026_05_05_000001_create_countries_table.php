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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name',120);
            $table->string('iso_code_2',2)->nullable()->unique();
            $table->string('iso_code_3',3)->nullable()->unique();
            $table->string('phone_code',10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
