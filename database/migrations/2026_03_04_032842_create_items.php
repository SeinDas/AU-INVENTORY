<?php

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
        Schema::create('items', function (Blueprint $table) {
            $table->id(); 
            $table->string('product_code', 100)->unique();
            $table->string('serial_no', 100)->default('0');
            $table->string('name'); 
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->foreignId('category_items_id')
                ->nullable()
                ->constrained('category_items')
                ->onDelete('set null');
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};