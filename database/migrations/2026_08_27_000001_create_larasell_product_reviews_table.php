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
            $table->foreignId('product_id')
                ->constrained('larasell_products')
                ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('value');
            $table->text('content')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'value']);
        });
    }
};
