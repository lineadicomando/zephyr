<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scopes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->boolean('protected')->default(false);
            $table->timestamp('pending_delete')->nullable()->index();
            $table->timestamps();
        });

        DB::table('scopes')->insert([
            'name' => 'Default',
            'slug' => 'default',
            'type' => 'company',
            'is_active' => true,
            'protected' => true,
            'pending_delete' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('scopes');
    }
};
