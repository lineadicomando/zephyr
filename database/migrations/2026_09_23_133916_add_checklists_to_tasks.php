<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The task type suggests the checklist template of its tasks. Each
     * inventory of a task gets its own checklist: the task_inventory rows
     * keep the position of the inventory when it was added to the task and
     * the completion data of the checklist.
     */
    public function up(): void
    {
        Schema::table('task_types', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')->nullable()->after('chart_color')->constrained();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')->nullable()->after('task_status_id')->constrained();
        });

        Schema::table('task_inventory', function (Blueprint $table) {
            $table->foreignId('inventory_position_id')->nullable()->after('inventory_id')->constrained()->nullOnDelete();
            $table->string('position_path')->nullable()->after('inventory_position_id');
            $table->text('note')->nullable()->after('position_path');
            $table->json('photos')->nullable()->after('note');
            $table->boolean('has_anomalies')->default(false)->after('photos');
            $table->timestamp('completed_at')->nullable()->after('has_anomalies');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('task_inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropConstrainedForeignId('inventory_position_id');
            $table->dropColumn(['position_path', 'note', 'photos', 'has_anomalies', 'completed_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checklist_template_id');
        });

        Schema::table('task_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checklist_template_id');
        });
    }
};
