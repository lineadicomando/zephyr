<?php

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Product;
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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->string('inventory_number', 20)->unique()->nullable();
            $table->foreignIdFor(Product::class);
            $table->string('serial_number', 50)->unique()->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->integer('stock')->default(0)->nullable();
            $table->string('url', 250)->nullable();
            $table->string('description', 250)->nullable();
            $table->string('summary', 1024)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
