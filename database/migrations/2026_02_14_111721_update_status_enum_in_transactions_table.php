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
        Schema::table('transactions', function (Blueprint $table) {
            // Modifier la colonne 'status' pour inclure toutes les valeurs possibles
            $table->enum('status', ['pending', 'processing', 'completed','success', 'failed'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Revenir à l'ancien enum si besoin (adapter selon l'ancien)
            $table->enum('status', ['pending', 'completed', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
