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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comune_id')->constrained('comunes')->restrictOnDelete();
            $table->string('name',120);
            $table->string('code',20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['comune_id','name']);
            $table->unique(['comune_id','code']);
            $table->index(['comune_id','is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
