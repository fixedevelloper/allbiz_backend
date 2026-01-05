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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            // Contrainte unique pour 1 seul investissement par utilisateur
            $table->unique('user_id');
        });
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('roulette_count')->default(0);
            $table->timestamps();
        });
        Schema::create('roulettes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commission_id')
                ->constrained('commissions')
                ->cascadeOnDelete();

            // Montant gagné après lancement
            $table->integer('amount')->nullable();

            // false = pas encore joué, true = déjà joué
            $table->boolean('status')->default(false);

            // Nombre de tours
            $table->enum('type', ['1step', '2step']);

            // Date d'exécution
            $table->timestamp('executed_at')->nullable();

            $table->timestamps();
        });


        Schema::create('operators', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // MTN, Orange, Moov
        $table->string('code'); // mtn, orange, moov
        $table->string('country_code'); // CM, CI, SN
        $table->timestamps();
    });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('reference')->nullable();
            $table->integer('amount');
            $table->enum('type', ['commission', 'withdrawal']);
            $table->enum('status', ['pending', 'success','failed']);
            $table->json('meta')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('operators')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_migrations');
    }
};
