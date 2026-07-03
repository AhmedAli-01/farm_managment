<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\Plot;
use App\Models\Partner;
use App\Models\CropCycle;
use App\Models\CropLog;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample farm data.
     */
    public function run(): void
    {
        // 1. Create a sample Field along the Shabelle Riverbank
        $field = Field::create([
            'name' => 'Shabelle Riverbank Sector',
            'total_area' => 20.00, // 20 hectares out of your 60
            'latitude' => 2.123456,   // Sample Mogadishu region coordinates
            'longitude' => 45.123456,
        ]);

        // 2. Create two sub-division Plots inside that Field
        $watermelonPlot = Plot::create([
            'field_id' => $field->id,
            'name' => 'Plot A - Crimson Sweet Watermelons',
            'size' => 2.00, // 2 hectares
            'status' => 'active',
        ]);

        $vegetablePlot = Plot::create([
            'field_id' => $field->id,
            'name' => 'Plot B - Mixed Vegetables',
            'size' => 1.50,
            'status' => 'fallow',
        ]);

        // 3. Create a Kalagoos Partner
        $partner = Partner::create([
            'name' => 'Cali Dheere',
            'phone' => '+252615551234', // Local Somali mobile format
            'type' => 'kalagoos',
        ]);

        // 4. Start an active Crop Cycle for the watermelons under Kalagoos
        $cycle = CropCycle::create([
            'plot_id' => $watermelonPlot->id,
            'partner_id' => $partner->id,
            'crop_name' => 'Crimson Sweet Watermelon',
            'operation_type' => 'kalagoos',
            'planting_date' => now()->subDays(20), // Planted 20 days ago
            'weather_at_planting' => 'Sunny, 32°C, Light River Breeze',
            'owner_profit_share' => 50.00, // 50/50 split
            'status' => 'growing',
        ]);

        // 5. Add an agronomic log entry with USD expense (Owner cost)
        CropLog::create([
            'crop_cycle_id' => $cycle->id,
            'log_type' => 'fertilizer',
            'title' => 'Initial NPK Application',
            'notes' => 'Applied baseline fertilizer package near drip lines.',
            'amount' => 120.00,
            'currency' => 'USD',
            'amount_in_usd' => 120.00,
        ]);

        // 6. Add an infection warning log with local SOS currency tracking
        CropLog::create([
            'crop_cycle_id' => $cycle->id,
            'log_type' => 'infection',
            'title' => 'Aphid Spotting on South Corner',
            'notes' => 'Spotted early aphid clustering. Hired local hands to clear weeds manually.',
            'amount' => 570000.00, // 570,000 Somali Shillings
            'currency' => 'SOS',
            'amount_in_usd' => 10.00, // Automatically calculated equivalent (e.g. $10 USD)
        ]);
    }
}