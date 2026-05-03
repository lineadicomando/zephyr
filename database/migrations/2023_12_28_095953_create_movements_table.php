<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\MovementType;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->datetime('date');
            $table->foreignIdFor(MovementType::class);
            $table->foreignIdFor(InventoryLocation::class, 'from_inventory_location_id')->nullable();
            $table->foreignIdFor(InventoryPosition::class, 'from_inventory_position_id')->nullable();
            $table->foreignIdFor(InventoryLocation::class, 'to_inventory_location_id')->nullable();
            $table->foreignIdFor(InventoryPosition::class, 'to_inventory_position_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
