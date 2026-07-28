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
        Schema::create('leave_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('reason_type')->default('absence'); // 'absence', 'retard', 'autre'
            $table->string('unit')->default('heures'); // 'jours', 'heures'
            $table->decimal('amount', 8, 2); // e.g. 4.00 (hours) or 2.00 (days)
            $table->decimal('days_deducted', 8, 2); // calculated equivalent in days (e.g. 4 hours = 0.5 days)
            $table->text('motif');
            $table->date('date_incident')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_deductions');
    }
};
