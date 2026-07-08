<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code', 'uq_folio_statuses_code');
            $table->index(['is_active', 'sort_order'], 'idx_folio_statuses_active_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_statuses');
    }
};
