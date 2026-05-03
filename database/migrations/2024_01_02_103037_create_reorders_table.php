<?php

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
        Schema::create('reorders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->foreignIdFor(Stock::class)->constrained()->onDelete('cascade');
            $table->integer('reorder_point');
            $table->integer('reorder_quantity')->nullable();
            $table->datetime('last_reorder_date')->nullable();
            $table->timestamps();

            $table->unique('stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorders');
    }
};
