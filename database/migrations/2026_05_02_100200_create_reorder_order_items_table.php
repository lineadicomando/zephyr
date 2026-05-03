<?php

use App\Models\Reorder;
use App\Models\Stock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes');
            $table->foreignId('reorder_order_id')->constrained('reorder_orders')->cascadeOnDelete();
            $table->foreignIdFor(Stock::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Reorder::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('current_stock');
            $table->integer('reorder_point');
            $table->integer('suggested_qty');
            $table->integer('ordered_qty')->nullable();
            $table->integer('received_qty')->nullable();
            $table->dateTime('last_reorder_date')->nullable();
            $table->timestamps();

            $table->unique(['reorder_order_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_order_items');
    }
};
