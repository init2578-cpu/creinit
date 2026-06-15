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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // Annuel, Maladie, Maternité/Paternité, Sans solde, Autre
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('motif');
            $table->string('status')->default('en_attente'); // en_attente, approuve, rejete
            $table->string('document_path')->nullable();
            $table->text('admin_commentaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
