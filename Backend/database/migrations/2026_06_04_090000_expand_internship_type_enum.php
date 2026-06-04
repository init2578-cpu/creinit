<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand the internship_type column to accept all 6 type values.
     * The original migration created an ENUM with only 2 values, but the app
     * needs: course_assistant, course_substitute, management_assistant,
     *         secretary_assistant, director_assistant, field_internship
     */
    public function up(): void
    {
        // Drop the PostgreSQL check constraint created by ->enum()
        try {
            DB::statement('ALTER TABLE internship_records DROP CONSTRAINT IF EXISTS internship_records_internship_type_check');
        } catch (\Exception $e) {
            // Silently ignore if already dropped or doesn't exist
        }

        // Convert the column to a plain string to accept all values
        Schema::table('internship_records', function (Blueprint $table) {
            $table->string('internship_type')->default('management_assistant')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to a string with no constraint (safe rollback)
        Schema::table('internship_records', function (Blueprint $table) {
            $table->string('internship_type')->default('management_assistant')->change();
        });
    }
};
