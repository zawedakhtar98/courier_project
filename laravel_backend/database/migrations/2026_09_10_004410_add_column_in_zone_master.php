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
        Schema::table('zone_master', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('zone_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zone_master', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
