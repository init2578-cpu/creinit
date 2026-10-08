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
        Schema::table('attendances', function (Blueprint $table): void {
            $table->boolean('is_advance_reported')->default(false)->after('status');
            $table->string('motif')->nullable()->after('is_advance_reported');
            $table->foreignId('reported_by')->nullable()->after('motif')->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable()->after('reported_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign(['reported_by']);
            $table->dropColumn(['is_advance_reported', 'motif', 'reported_by', 'reported_at']);
        });
    }
};
