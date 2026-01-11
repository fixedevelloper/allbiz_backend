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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('src');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->index();

            $table->boolean('downloadable')->default(false);
            $table->boolean('is_promotion')->default(false);

            $table->decimal('price', 12, 2);
            $table->decimal('promotion_price', 12, 2)->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('image_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('image_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'image_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('amount_rest', 12, 2)->default(0);

            $table->string('operator')->nullable()->index();
            $table->string('reference_id')->nullable()->index();

            $table->timestamp('confirmed_at')->nullable();

            $table->string('status')->default('pending')->index();

            $table->json('meta')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('quantity')->default(1);

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'order_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('withdraw_accounts');
    }
};
