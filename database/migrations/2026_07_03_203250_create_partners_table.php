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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Full name of the partner or investor
            $table->string('phone')->nullable(); // Crucial for mobile money (EVC Plus) and coordination

            // This categorizes the type of partnership
            // e.g., 'kalagoos' for local sharecroppers, 'investor' for absentee funders
            $table->string('type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};