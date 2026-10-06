<?php

declare(strict_types=1);

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
        Schema::create('exam_rattrapages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('titre')->nullable();
            $table->dateTime('scheduled_at');
            $table->integer('duree_minutes');
            $table->text('instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_rattrapage_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_rattrapage_id')->constrained('exam_rattrapages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->text('motif_justification')->nullable();
            $table->string('status', 30)->default('scheduled'); // scheduled, completed, absent
            $table->timestamps();

            $table->unique(['exam_rattrapage_id', 'user_id']);
        });

        Schema::table('exam_results', function (Blueprint $table): void {
            $table->boolean('is_rattrapage')->default(false)->after('score');
            $table->foreignId('exam_rattrapage_id')->nullable()->after('is_rattrapage')->constrained('exam_rattrapages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->dropForeign(['exam_rattrapage_id']);
            $table->dropColumn(['is_rattrapage', 'exam_rattrapage_id']);
        });

        Schema::dropIfExists('exam_rattrapage_user');
        Schema::dropIfExists('exam_rattrapages');
    }
};
