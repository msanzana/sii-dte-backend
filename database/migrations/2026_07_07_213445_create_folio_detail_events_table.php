<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_detail_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('folio_detail_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('external_system_id')->nullable();

            $table->unsignedInteger('branch_office_number')->nullable();
            $table->unsignedInteger('facility_number')->nullable();

            $table->string('event_code', 80);
            $table->string('from_status_code', 50)->nullable();
            $table->string('to_status_code', 50)->nullable();

            $table->text('message')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('payload_json')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['folio_detail_id'], 'idx_folio_detail_events_detail_id');
            $table->index(['company_id', 'event_code'], 'idx_folio_detail_events_company_event');
            $table->index(
                ['company_id', 'external_system_id', 'branch_office_number', 'facility_number'],
                'idx_folio_detail_events_distribution'
            );

            $table->foreign('folio_detail_id')
                ->references('id')
                ->on('folio_details');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('external_system_id')
                ->references('id')
                ->on('external_systems');

            $table->foreign('user_id')
                ->references('id')
                ->on('auth_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_detail_events');
    }
};