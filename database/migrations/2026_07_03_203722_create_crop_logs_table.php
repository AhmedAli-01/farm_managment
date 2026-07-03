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
        Schema::create('crop_logs', function (Blueprint $table) {
            $table->id();

            // Connects this log item directly to a specific crop cycle
            $table->foreignId('crop_cycle_id')->constrained()->onDelete('cascade');

            // Categorizes the action: progress, fertilizer, infection, treatment, harvest
            $table->string('log_type');

            $table->string('title'); // e.g., "NPK 15-15-15 Application" or "Whitefly Spreading"
            $table->text('notes')->nullable(); // Detailed field notes on application rates or observations

            // Your progress photo modification path lives here
            $table->string('photo_path')->nullable();

            // Your dual-currency tracking columns live here
            $table->decimal('amount', 15, 2)->nullable(); // The actual raw cash spent/earned (e.g., 500000.00)
            $table->string('currency', 3)->default('USD'); // 'USD' or 'SOS'

            // The unified accounting column (SOS amounts get converted to USD values here)
            $table->decimal('amount_in_usd', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_logs');
    }
};