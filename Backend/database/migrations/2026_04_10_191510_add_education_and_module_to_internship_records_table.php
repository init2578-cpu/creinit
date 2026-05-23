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
        Schema::table('internship_records', function (Blueprint $table) {
            $table->string('niveau_etude')->nullable();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internship_records', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn(['niveau_etude', 'module_id']);
        });
    }
};
