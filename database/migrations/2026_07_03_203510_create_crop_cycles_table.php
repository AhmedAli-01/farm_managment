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
        Schema::create('crop_cycles', function (Blueprint $table) {
            $table->id();

            // Connects this cycle to a specific plot
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            // Connects to a partner. Nullable because "Direct Operations" don't have a partner.
            $table->foreignId('partner_id')->nullable()->constrained()->onDelete('set null');

            $table->string('crop_name'); // e.g., "Crimson Sweet Watermelon", "Beetroot"

            // This dictates our financial logic: 'direct', 'kalagoos', or 'managed'
            $table->string('operation_type');

            $table->date('planting_date');

            // Your custom weather modification lives here
            $table->string('weather_at_planting')->nullable(); // e.g., "Sunny, 34°C, Dry"

            // Stores the profit share split (e.g., 50.00 for a 50/50 split)
            $table->decimal('owner_profit_share', 5, 2)->default(100.00);

            $table->string('status')->default('growing'); // growing, harvested, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_cycles');
    }
};