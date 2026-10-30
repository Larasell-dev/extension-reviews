<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('larasell_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('larasell_products')->cascadeOnDelete();
            // The review outlives its customer, so it keeps its own copy of their name.
            $table->foreignId('customer_id')->nullable()->constrained('larasell_customers')->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larasell_product_reviews');
    }
};
