<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['scope_id', 'inventory_id', 'inventory_position_id'], 'stocks_scope_inventory_position_unique');
        });
    }

    /**
     * MySQL/MariaDB use the unique index for the scope_id foreign key once it
     * exists: add a dedicated index before dropping it.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->index('scope_id');
            $table->dropUnique('stocks_scope_inventory_position_unique');
        });
    }
};
