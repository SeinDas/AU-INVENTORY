<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Remove the old category link from items
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // Drop foreign key first
            $table->dropColumn('category_id');
        });

        // Remove redundant stock tables
        Schema::dropIfExists('stock_in');
        Schema::dropIfExists('stock_out');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
