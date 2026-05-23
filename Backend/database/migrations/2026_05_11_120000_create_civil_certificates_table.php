<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civil_certificates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 50);
            $table->string('reference_number')->unique();
            $table->string('applicant_first_name')->nullable();
            $table->string('applicant_last_name')->nullable();
            $table->string('applicant_cni')->nullable();
            $table->json('data')->nullable(); // Stores diverse fields: adresse, ressource, temoins, etc.
            
            $table->string('status')->default('pending'); // pending, validated, rejected
            
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_certificates');
    }
};
