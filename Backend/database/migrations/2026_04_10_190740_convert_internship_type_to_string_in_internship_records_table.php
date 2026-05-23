<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the check constraint if it exists (PostgreSQL specific fix)
        try {
            DB::statement('ALTER TABLE internship_records DROP CONSTRAINT IF EXISTS internship_records_internship_type_check');
        } catch (\Exception $e) {
            // Silently ignore if already dropped
        }

        Schema::table('internship_records', function (Blueprint $table) {
            $table->string('internship_type')->default('management_assistant')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We leave it as string to avoid breaking production data if new types were added
    }
};
