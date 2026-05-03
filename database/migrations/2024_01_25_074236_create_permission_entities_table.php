<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_entities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->morphs('entity');
            $table->boolean('view')->default(true);
            $table->boolean('update')->default(false);
            $table->boolean('delete')->default(false);
            $table->boolean('force_delete')->default(false);
            $table->boolean('restore')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_entities');
    }
};
