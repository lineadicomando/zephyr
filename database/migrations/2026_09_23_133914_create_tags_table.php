<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Global tags of the inventories and of the products: the inventories
     * inherit the tags of their product.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['inventory_id', 'tag_id']);
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('inventory_tag');
        Schema::dropIfExists('tags');
    }
};
