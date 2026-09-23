<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Answers of the checklist of an inventory in a task. The item label,
     * type, unit and required flag are copied: the history stays readable
     * after the template changes or the item is deleted.
     */
    public function up(): void
    {
        Schema::create('checklist_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_inventory_id')->constrained('task_inventory')->cascadeOnDelete();
            $table->foreignId('checklist_template_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->string('label');
            $table->string('response_type');
            $table->string('unit')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('value')->nullable();
            $table->text('note')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->timestamps();
            $table->unique(['task_inventory_id', 'checklist_template_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_results');
    }
};
