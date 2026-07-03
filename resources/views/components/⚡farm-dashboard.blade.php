<?php

use Livewire\Component;
use App\Models\Field;
use App\Models\CropCycle;
use App\Models\CropLog;

new class extends Component {
    // Dashboard Stats
    public $totalFields;
    public $activeCycles;
    public $totalExpenses;
    public $fields;
    public $activeCycleOptions;

    // Form Form Fields State
    public $crop_cycle_id;
    public $log_type = 'progress';
    public $title;
    public $notes;
    public $amount;
    public $currency = 'USD';

    // Runs when the component first loads
    public function mount()
    {
        $this->refreshDashboard();
        
        // Pre-select the first available crop cycle if one exists
        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        }
    }

    // Centralized method to pull fresh data from MariaDB
    public function refreshDashboard()
    {
        $this->totalFields = Field::count();
        $this->activeCycles = CropCycle::where('status', 'growing')->count();
        $this->totalExpenses = CropLog::sum('amount_in_usd');
        $this->fields = Field::with('plots.cropCycles.partner')->get();
        
        // Fetch active cycles specifically to populate our form dropdown
        $this->activeCycleOptions = CropCycle::where('status', 'growing')->get();
    }

    // Action that runs when you click "Save Log Entry"
    public function saveLog()
    {
        // Simple data validation
        $this->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'log_type' => 'required|string',
            'title' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,SOS',
        ]);

        // Dual-Currency Conversion Logic
        // Based on our database seeder: 570,000 SOS = $10 USD (1 USD = 57,000 SOS)
        $exchangeRate = 57000; 
        $calculatedUsd = null;

        if ($this->amount) {
            if ($this->currency === 'SOS') {
                $calculatedUsd = $this->amount / $exchangeRate;
            } else {
                $calculatedUsd = $this->amount; // Already in USD
            }
        }

        // Save straight to MariaDB
        CropLog::create([
            'crop_cycle_id' => $this->crop_cycle_id,
            'log_type' => $this->log_type,
            'title' => $this->title,
            'notes' => $this->notes,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'amount_in_usd' => $calculatedUsd,
        ]);

        // Clear out the form text inputs for the next entry
        $this->title = '';
        $this->notes = '';
        $this->amount = null;

        // Instantly refresh calculations on the dashboard
        $this->refreshDashboard();
    }
}; ?>

<!-- HTML Template -->
<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Top Header Bar -->
    <div class="flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">60 ha Farm Management</h1>
            <p class="text-gray-500">Shabelle Riverbank • Mogadishu, Somalia</p>
        </div>
        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-sm font-semibold">
            Operational Dashboard
        </div>
    </div>

    <!-- Quick Metrics Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Fields Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-600">
            <div class="text-sm font-medium text-gray-500 uppercase">Managed Sectors</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">{{ $totalFields }}</span>
                <span class="text-green-600 bg-green-50 px-2.5 py-0.5 rounded-full text-xs font-medium">Land Matrix</span>
            </div>
        </div>

        <!-- Active Cycles Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-600">
            <div class="text-sm font-medium text-gray-500 uppercase">Active Crop Cycles</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">{{ $totalCycles = $activeCycles }}</span>
                <span class="text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full text-xs font-medium">In Production</span>
            </div>
        </div>

        <!-- Financial Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-amber-500">
            <div class="text-sm font-medium text-gray-500 uppercase">Total Logged Expenses</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">${{ number_format($totalExpenses, 2) }}</span>
                <span class="text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full text-xs font-medium">USD Balance</span>
            </div>
        </div>
    </div>

    <!-- Interactive Section Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Columns: Sectors & Plot Layout -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-bold text-gray-800">Sectors & Plot Overview</h2>

            @foreach($fields as $field)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-green-800">{{ $field->name }}</h3>
                        <p class="text-xs text-gray-500">GPS: {{ $field->latitude ?? 'N/A' }}, {{ $field->longitude ?? 'N/A' }}</p>
                    </div>
                    <span class="bg-green-600 text-white text-xs px-3 py-1 rounded-full font-semibold">
                        {{ $field->total_area }} Hectares
                    </span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($field->plots as $plot)
                    <div class="border border-gray-100 rounded-lg p-4 bg-slate-50 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $plot->name }}</h4>
                                <p class="text-xs text-gray-500">Size: {{ $plot->size }} ha</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $plot->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $plot->status }}
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-200/60 text-xs">
                            @if($plot->status === 'active' && $plot->cropCycles->first())
                            @php $activeCycle = $plot->cropCycles->first(); @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Current Crop:</span>
                                    <span class="font-bold text-blue-700">{{ $activeCycle->crop_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Model:</span>
                                    <span class="font-semibold text-gray-700 capitalize">{{ $activeCycle->operation_type }}</span>
                                </div>
                                @if($activeCycle->partner)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Partner:</span>
                                    <span class="text-gray-700 font-medium">{{ $activeCycle->partner->name }}</span>
                                </div>
                                @endif
                            </div>
                            @else
                            <p class="text-gray-400 italic">No active planting cycle recorded.</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm col-span-2">No sub-plots created for this sector yet.</p>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>

        <!-- Right 1 Column: Live Activity/Expense Logger Form -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-gray-800">Field Operations Log</h2>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-md font-bold text-slate-700 border-b border-gray-100 pb-2 mb-4">Log New Action / Expense</h3>

                <!-- Livewire Form submit event interceptor -->
                <form wire:submit.prevent="saveLog" class="space-y-4">

                    <!-- Crop Cycle Target Selector -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Target Crop Plot</label>
                        <select wire:model="crop_cycle_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($activeCycleOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->crop_name }} ({{ $option->plot->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Type -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Log Category</label>
                        <select wire:model="log_type" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500">
                            <option value="progress">Progress Observation</option>
                            <option value="fertilizer">Fertilizer Application</option>
                            <option value="infection">Infection Alert / Pest</option>
                            <option value="treatment">Treatment / Spraying</option>
                            <option value="harvest">Harvest Record</option>
                        </select>
                    </div>

                    <!-- Log Title -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Action Title</label>
                        <input type="text" wire:model="title" placeholder="e.g., Weed clearing laborers" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <!-- Field Notes -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Field Notes (Optional)</label>
                        <textarea wire:model="notes" rows="3" placeholder="Specify rates or observations..." class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Financial Tracking Block -->
                    <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Cost Tracking (If Any)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Raw Cash Amount Input -->
                            <input type="number" step="0.01" wire:model="amount" placeholder="0.00" class="col-span-2 text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500">

                            <!-- Currency Toggle Selector -->
                            <select wire:model="currency" class="text-sm border-gray-300 rounded-lg p-2 bg-white font-bold text-gray-700 focus:ring-blue-500">
                                <option value="USD">USD ($)</option>
                                <option value="SOS">SOS (Sh.)</option>
                            </select>
                        </div>
                        @if($currency === 'SOS' && $amount > 0)
                        <p class="text-[11px] text-gray-500 mt-1 italic">
                            Automatically logging as: ${{ number_format($amount / 57000, 2) }} USD
                        </p>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition ease-in-out duration-150 text-sm">
                        Save Log Entry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
