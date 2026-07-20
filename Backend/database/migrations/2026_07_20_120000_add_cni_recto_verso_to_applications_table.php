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
            $table->string('cni_recto_path')->nullable()->after('cni_path');
            $table->string('cni_verso_path')->nullable()->after('cni_recto_path');
            $table->string('cni_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['cni_recto_path', 'cni_verso_path']);
        });
    }
};
