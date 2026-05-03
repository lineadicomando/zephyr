<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\Stock;
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
        Schema::create('movement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->foreignIdFor(Movement::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Inventory::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Stock::class, 'incoming_stock_id')->nullable()->constrained('stocks')->onDelete('set null');
            $table->foreignIdFor(Stock::class, 'outcoming_stock_id')->nullable()->constrained('stocks')->onDelete('set null');
            $table->string('inventory_summary', 1024)->nullable();
            $table->integer('stock')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_items');
    }
};
