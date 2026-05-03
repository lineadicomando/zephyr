<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Product;
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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->foreignIdFor(ProductGroup::class)->nullable();
            $table->foreignIdFor(ProductType::class)->nullable();
            $table->foreignIdFor(ProductBrand::class)->nullable();
            $table->foreignIdFor(ProductModel::class)->nullable();
            $table->foreignIdFor(Product::class)->nullable();
            $table->foreignIdFor(Inventory::class);
            $table->foreignIdFor(InventoryLocation::class)->nullable();
            $table->foreignIdFor(InventoryPosition::class)->nullable();
            $table->string('inventory_summary', 1024)->nullable();
            $table->string('path', 800);
            $table->integer('stock')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
