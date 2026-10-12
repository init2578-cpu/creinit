<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('motif_remise_en_attente_at')->nullable()->after('motif_remise_en_attente');
        });

        DB::table('applications')
            ->whereNotNull('motif_remise_en_attente')
            ->whereNull('motif_remise_en_attente_at')
            ->update(['motif_remise_en_attente_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('motif_remise_en_attente_at');
        });
    }
};
