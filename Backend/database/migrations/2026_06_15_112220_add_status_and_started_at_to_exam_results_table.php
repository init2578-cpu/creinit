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
        Schema::table('exam_results', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->change();
            $table->timestamp('finished_at')->nullable()->change();
            $table->string('status')->default('completed')->after('user_id');
            $table->timestamp('started_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable(false)->change();
            $table->timestamp('finished_at')->nullable(false)->change();
            $table->dropColumn(['status', 'started_at']);
        });
    }
};
