<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Global checklist templates and their items. An item with tags applies
     * only to the inventories having at least one of them.
     */
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->string('label');
            $table->string('help')->nullable();
            $table->string('response_type');
            $table->string('unit')->nullable();
            $table->json('options')->nullable();
            $table->decimal('min', 15, 3)->nullable();
            $table->decimal('max', 15, 3)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_template_item_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['checklist_template_item_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_template_item_tag');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklist_templates');
    }
};
