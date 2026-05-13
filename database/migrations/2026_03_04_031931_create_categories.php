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
        Schema::create('categories', function (Blueprint $table) {
            // id INT PRIMARY KEY AUTO_INCREMENT
            $table->id(); 

            // name VARCHAR(255) NOT NULL
            $table->string('name'); 

            // parent_id INT NULL, with foreign key constraint to categories.id
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');

            // Recommended: Adds created_at and updated_at columns
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};