<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_user', function (Blueprint $table): void {
            $table->foreignId('scope_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['scope_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_user');
    }
};
