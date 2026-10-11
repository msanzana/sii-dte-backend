<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table
                ->string('logo_path', 255)
                ->nullable()
                ->after('sii_office_name');

            $table
                ->string('logo_mime_type', 50)
                ->nullable()
                ->after('logo_path');

            $table
                ->char('logo_sha256', 64)
                ->nullable()
                ->after('logo_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'logo_mime_type',
                'logo_sha256',
            ]);
        });
    }
};