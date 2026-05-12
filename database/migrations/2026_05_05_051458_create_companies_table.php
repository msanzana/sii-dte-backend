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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            // RUT Completo
            $table->string('rut',12)->unique();
            // Parte numérica del RUT
            $table->string('rut_body',8);
            // Dígito verificador
            $table->string('rut_dv',1);
            // Razón social
            $table->string('legal_name',120);
            // Nombre de fantasía
            $table->string('trade_name',120)->nullable();
            //Giro
            $table->string('giro',150)->nullable();
            $table->string('address',150);
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->string('dte_email',150)->nullable();
            $table->string('resolution_number',20)->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('sii_environment',10)->default('cert');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['city_id']);
            $table->index(['is_active', 'sii_environment']);
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
