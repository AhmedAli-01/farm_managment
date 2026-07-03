<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            // This links the plot directly to a parent field. 
            // If a field is deleted, its plots are automatically cleaned up too.
            $table->foreignId('field_id')->constrained()->onDelete('cascade');

            $table->string('name'); // e.g., "Plot 1 - Watermelons"
            $table->decimal('size', 8, 2); // Size of this specific sub-division in hectares
            $table->string('status')->default('fallow'); // fallow, active, resting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};