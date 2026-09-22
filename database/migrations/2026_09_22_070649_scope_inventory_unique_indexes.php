<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique(['inventory_number']);
            $table->dropUnique(['serial_number']);

            $table->unique(['scope_id', 'inventory_number']);
            $table->unique(['scope_id', 'serial_number']);
        });
    }

    /**
     * MySQL/MariaDB use the composite unique indexes for the scope_id foreign
     * key: add a dedicated index before dropping them.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->index('scope_id');
            $table->dropUnique(['scope_id', 'inventory_number']);
            $table->dropUnique(['scope_id', 'serial_number']);

            $table->unique('inventory_number');
            $table->unique('serial_number');
        });
    }
};
