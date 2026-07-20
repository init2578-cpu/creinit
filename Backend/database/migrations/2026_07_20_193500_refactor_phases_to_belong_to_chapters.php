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
        // 1. Remove phase_id from chapters if it exists
        if (Schema::hasColumn('chapters', 'phase_id')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->dropForeign(['phase_id']);
                $table->dropColumn('phase_id');
            });
        }

        // 2. Clear existing test phases to avoid NOT NULL constraint violation on chapter_id
        \Illuminate\Support\Facades\DB::table('phases')->truncate();

        // 3. Refactor phases to belong to chapters instead of modules
        Schema::table('phases', function (Blueprint $table) {
            if (Schema::hasColumn('phases', 'module_id')) {
                $table->dropForeign(['module_id']);
                $table->dropColumn('module_id');
            }
            $table->foreignId('chapter_id')->after('id')->constrained('chapters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn('chapter_id');
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('cascade');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->foreignId('phase_id')->nullable()->constrained('phases')->nullOnDelete();
        });
    }
};
