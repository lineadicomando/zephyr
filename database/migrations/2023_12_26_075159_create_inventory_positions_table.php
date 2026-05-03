<?php

use App\Models\InventoryLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->foreignIdFor(InventoryLocation::class);
            $table->string('path', 510);
            $table->string('name', 255);
            $table->boolean('default')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['scope_id', 'inventory_location_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_positions');
    }
};
