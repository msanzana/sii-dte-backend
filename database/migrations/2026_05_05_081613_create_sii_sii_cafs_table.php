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
        Schema::create('sii_cafs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->unsignedSmallInteger('dte_type');
            $table->unsignedBigInteger('folio_start');
            $table->unsignedBigInteger('folio_end');
            $table->unsignedBigInteger('last_asigned_folio')->nullable();
            $table->string('caf_xml_path',255);
            $table->longText('private_key_pem_encrypted');
            $table->longText('public_key_pem')->nullable();
            $table->date('authorized_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['company_id','dte_type', 'is_active']);
            $table->index(['company_id','dte_type','folio_start', 'folio_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sii_sii_cafs');
    }
};
