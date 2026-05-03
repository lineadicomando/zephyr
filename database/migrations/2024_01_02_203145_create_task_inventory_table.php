<?php

use App\Models\Task;
use App\Models\Inventory;
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
        Schema::create('task_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Task::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Inventory::class)->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_inventory');
    }
};
