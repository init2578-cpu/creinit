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
        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('type')->default('reussite')->after('module_id'); // 'reussite' | 'participation'
            $table->decimal('score', 4, 2)->nullable()->after('type'); // Note moyenne sur 20
            $table->foreignId('group_id')->nullable()->after('score')->constrained('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropColumn(['group_id', 'score', 'type']);
        });
    }
};
