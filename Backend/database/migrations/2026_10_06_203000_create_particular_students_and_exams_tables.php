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
        if (!Schema::hasColumn('users', 'is_particulier')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_particulier')->default(false)->after('is_active');
            });
        }

        if (!Schema::hasTable('user_particular_modules')) {
            Schema::create('user_particular_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
                $table->timestamp('assigned_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'module_id']);
            });
        }

        if (!Schema::hasTable('exam_particular_users')) {
            Schema::create('exam_particular_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['exam_id', 'user_id']);
            });
        }

        if (!Schema::hasColumn('exams', 'is_exclusive_directeur')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->boolean('is_exclusive_directeur')->default(false)->after('are_grades_published');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('exams', 'is_exclusive_directeur')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('is_exclusive_directeur');
            });
        }

        Schema::dropIfExists('exam_particular_users');
        Schema::dropIfExists('user_particular_modules');

        if (Schema::hasColumn('users', 'is_particulier')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_particulier');
            });
        }
    }
};
