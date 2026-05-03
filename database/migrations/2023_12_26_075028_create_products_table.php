<?php

use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProductGroup::class)->nullable();
            $table->foreignIdFor(ProductType::class)->nullable();
            $table->foreignIdFor(ProductBrand::class)->nullable();
            $table->foreignIdFor(ProductModel::class)->nullable();
            $table->string('code', 255)->nullable();
            $table->string('name', 255);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
