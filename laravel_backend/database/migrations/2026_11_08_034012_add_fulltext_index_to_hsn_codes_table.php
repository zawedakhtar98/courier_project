<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hsn_codes', function (Blueprint $table) {
            // Index for numeric HSN code lookup
            $table->index('hsn_code');
            $table->index('status');
        });

        // Add Full-Text index on description (MySQL / MariaDB)
        DB::statement('ALTER TABLE hsn_codes ADD FULLTEXT fulltext_hsn_desc (description)');
    }

    public function down(): void
    {
        Schema::table('hsn_codes', function (Blueprint $table) {
            $table->dropIndex(['hsn_code']);
            $table->dropIndex(['status']);
        });
        DB::statement('ALTER TABLE hsn_codes DROP INDEX fulltext_hsn_desc');
    }
};
