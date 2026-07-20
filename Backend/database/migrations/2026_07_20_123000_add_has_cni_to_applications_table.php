<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->boolean('has_cni')->default(true)->after('cni_verso_path');
            $table->string('other_identity_doc_path')->nullable()->after('has_cni');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['has_cni', 'other_identity_doc_path']);
        });
    }
};
