<?php

use Livewire\Component;
use App\Models\Field;
use App\Models\Plot;
use App\Models\Partner;
use App\Models\CropCycle;
use App\Models\CropLog;

new class extends Component {
    // Shared Dashboard Datasets
    public $totalFields;
    public $activeCycles;
    public $totalExpenses;
    public $fields;
    public $activeCycleOptions;
    
    // Dropdown Choices Lists
    public $allPlotOptions;
    public $partnerOptions;

    // Form 1: Operational Logger State
    public $crop_cycle_id;
    public $log_type = 'progress';
    public $title;
    public $notes;
    public $amount;
    public $currency = 'USD';

    // Form 2: Launch Crop Cycle State
    public $new_plot_id;
    public $new_crop_name;
    public $new_operation_type = 'direct';
    public $new_owner_share = 100.00;
    public $new_weather_at_planting;
    public $partner_mode = 'existing'; // 'existing' or 'new'
    public $new_partner_id;
    public $new_partner_name;
    public $new_partner_phone;
    public $new_partner_type = 'kalagoos';

    // Runs once when component instantiates
    public function mount()
    {
        $this->refreshDashboard();
        
        // Auto-select starting layout elements if entries exist
        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        }
        if ($this->allPlotOptions->isNotEmpty()) {
            $this->new_plot_id = $this->allPlotOptions->first()->id;
        }
    }

    // Query engine to re-fetch the fresh status maps
    public function refreshDashboard()
    {
        $this->totalFields = Field::count();
        $this->activeCycles = CropCycle::where('status', 'growing')->count();
        $this->totalExpenses = CropLog::sum('amount_in_usd');
        $this->fields = Field::with('plots.cropCycles.partner')->get();
        
        $this->activeCycleOptions = CropCycle::where('status', 'growing')->get();
        $this->allPlotOptions = Plot::all();
        $this->partnerOptions = Partner::all();
    }

    // Submit Handler for Form 1 (Expenses & Events Logs)
    public function saveLog()
    {
        $this->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'log_type' => 'required|string',
            'title' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,SOS',
        ]);

        $exchangeRate = 57000; 
        $calculatedUsd = null;

        if ($this->amount) {
            $calculatedUsd = ($this->currency === 'SOS') ? ($this->amount / $exchangeRate) : $this->amount;
        }

        CropLog::create([
            'crop_cycle_id' => $this->crop_cycle_id,
            'log_type' => $this->log_type,
            'title' => $this->title,
            'notes' => $this->notes,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'amount_in_usd' => $calculatedUsd,
        ]);

        $this->title = '';
        $this->notes = '';
        $this->amount = null;

        $this->refreshDashboard();
    }

    // Submit Handler for Form 2 (Launch Seasons & Setup Partnerships)
    public function launchCycle()
    {
        $this->validate([
            'new_plot_id' => 'required|exists:plots,id',
            'new_crop_name' => 'required|string|max:255',
            'new_operation_type' => 'required|in:direct,kalagoos,managed',
            'new_owner_share' => 'required|numeric|between:0,100',
        ]);

        $finalPartnerId = null;

        // Run validation rules contextually based on setup choice
        if ($this->new_operation_type !== 'direct') {
            if ($this->partner_mode === 'new') {
                $this->validate([
                    'new_partner_name' => 'required|string|max:255',
                    'new_partner_phone' => 'nullable|string',
                    'new_partner_type' => 'required|in:kalagoos,investor',
                ]);

                $partner = Partner::create([
                    'name' => $this->new_partner_name,
                    'phone' => $this->new_partner_phone,
                    'type' => $this->new_partner_type,
                ]);
                $finalPartnerId = $partner->id;
            } else {
                $this->validate([
                    'new_partner_id' => 'required|exists:partners,id',
                ]);
                $finalPartnerId = $this->new_partner_id;
            }
        }

        // 1. Generate the primary crop cycle record
        $cycle = CropCycle::create([
            'plot_id' => $this->new_plot_id,
            'partner_id' => $finalPartnerId,
            'crop_name' => $this->new_crop_name,
            'operation_type' => $this->new_operation_type,
            'planting_date' => now(),
            'weather_at_planting' => $this->new_weather_at_planting ?: 'Sunny, Dry Baseline',
            'owner_profit_share' => $this->new_owner_share,
            'status' => 'growing',
        ]);

        // 2. Automatically flip the plot entity status to Active
        Plot::find($this->new_plot_id)->update(['status' => 'active']);

        // 3. Inject baseline system operation log
        CropLog::create([
            'crop_cycle_id' => $cycle->id,
            'log_type' => 'progress',
            'title' => 'Crop Cycle Launched',
            'notes' => "System initialized cultivation sequence under structural model: " . strtoupper($this->new_operation_type),
        ]);

        // Clean slate text properties
        $this->new_crop_name = '';
        $this->new_weather_at_planting = '';
        $this->new_partner_name = '';
        $this->new_partner_phone = '';
        
        // Match form state selections back to functional items
        $this->refreshDashboard();
        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <div class="flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">60 ha Farm Management</h1>
            <p class="text-gray-500">Shabelle Riverbank • Mogadishu, Somalia</p>
        </div>
        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-sm font-semibold">
            Operational Dashboard
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-600">
            <div class="text-sm font-medium text-gray-500 uppercase">Managed Sectors</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">{{ $totalFields }}</span>
                <span class="text-green-600 bg-green-50 px-2.5 py-0.5 rounded-full text-xs font-medium">Land Matrix</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-600">
            <div class="text-sm font-medium text-gray-500 uppercase">Active Crop Cycles</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">{{ $activeCycles }}</span>
                <span class="text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full text-xs font-medium">In Production</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-amber-500">
            <div class="text-sm font-medium text-gray-500 uppercase">Total Logged Expenses</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-4xl font-bold text-gray-900">${{ number_format($totalExpenses, 2) }}</span>
                <span class="text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full text-xs font-medium">USD Balance</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-bold text-gray-800">Sectors & Plot Overview</h2>

            @foreach($fields as $field)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-green-800">{{ $field->name }}</h3>
                        <p class="text-xs text-gray-500">GPS Mapping: {{ $field->latitude ?? 'N/A' }}, {{ $field->longitude ?? 'N/A' }}</p>
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
                                <p class="text-xs text-gray-500">Size Matrix: {{ $plot->size }} ha</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $plot->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $plot->status }}
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-200/60 text-xs">
                            @if($plot->status === 'active' && $plot->cropCycles->where('status', 'growing')->first())
                            @php $activeCycle = $plot->cropCycles->where('status', 'growing')->first(); @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Current Crop:</span>
                                    <span class="font-bold text-blue-700">{{ $activeCycle->crop_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Model Split:</span>
                                    <span class="font-semibold text-gray-700 capitalize">{{ $activeCycle->operation_type }} ({{ intval($activeCycle->owner_profit_share) }}%)</span>
                                </div>
                                @if($activeCycle->partner)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Linked Partner:</span>
                                    <span class="text-gray-700 font-medium">{{ $activeCycle->partner->name }}</span>
                                </div>
                                @endif
                            </div>
                            @else
                            <p class="text-gray-400 italic">Fallow field space. Ready for activation.</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm col-span-2">No individual plots defined inside this sector sector block.</p>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>

        <div class="space-y-6">

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-md font-bold text-slate-700 border-b border-gray-100 pb-2 mb-4">Launch New Crop Cycle</h3>

                <form wire:submit.prevent="launchCycle" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Target Land Space</label>
                        <select wire:model="new_plot_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($allPlotOptions as $pOption)
                            <option value="{{ $pOption->id }}">{{ $pOption->name }} ({{ $pOption->status }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Crop Classification</label>
                        <input type="text" wire:model="new_crop_name" placeholder="e.g., Beetroot Block C, Okra" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Operation Structure</label>
                        <select wire:model.live="new_operation_type" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500">
                            <option value="direct">Direct Operation (100% Owner)</option>
                            <option value="kalagoos">Kalagoos Agreement (Sharecropping)</option>
                            <option value="managed">Managed Investment (Absentee Funder)</option>
                        </select>
                    </div>

                    @if($new_operation_type !== 'direct')
                    <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100/50 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-blue-800 mb-1">Partner Definition</label>
                            <div class="flex items-center space-x-4 text-xs font-medium">
                                <label class="flex items-center space-x-1">
                                    <input type="radio" value="existing" wire:model.live="partner_mode" class="text-blue-600 focus:ring-blue-500">
                                    <span>Existing Profile</span>
                                </label>
                                <label class="flex items-center space-x-1">
                                    <input type="radio" value="new" wire:model.live="partner_mode" class="text-blue-600 focus:ring-blue-500">
                                    <span>Register New</span>
                                </label>
                            </div>
                        </div>

                        @if($partner_mode === 'existing')
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Select Partner Profile</label>
                            <select wire:model="new_partner_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-white focus:ring-blue-500">
                                <option value="">-- Choose Profile --</option>
                                @foreach($partnerOptions as $ptOpt)
                                <option value="{{ $ptOpt->id }}">{{ $ptOpt->name }} ({{ ucfirst($ptOpt->type) }})</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="space-y-2">
                            <input type="text" wire:model="new_partner_name" placeholder="Full Name" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-white focus:ring-blue-500">
                            <input type="text" wire:model="new_partner_phone" placeholder="Phone (e.g. +25261...)" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-white focus:ring-blue-500">
                            <input type="hidden" wire:model="new_partner_type" value="{{ $new_operation_type }}">
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Your Retained Profit Split (%)</label>
                            <input type="number" step="0.1" wire:model="new_owner_share" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-white focus:ring-blue-500">
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Weather Conditions at Planting</label>
                        <input type="text" wire:model="new_weather_at_planting" placeholder="e.g., Overcast, 31°C, Humidity high" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500">
                    </div>

                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition">
                        Initialize Crop Cycle
                    </button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-md font-bold text-slate-700 border-b border-gray-100 pb-2 mb-4">Log New Action / Expense</h3>

                <form wire:submit.prevent="saveLog" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Target Crop Plot</label>
                        <select wire:model="crop_cycle_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500">
                            @forelse($activeCycleOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->crop_name }} ({{ $option->plot->name }})</option>
                            @empty
                            <option value="">-- No Active Production Cycles --</option>
                            @endforelse
                        </select>
                    </div>

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

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Action Title</label>
                        <input type="text" wire:model="title" placeholder="e.g., Drip line inspection" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Field Notes</label>
                        <textarea wire:model="notes" rows="2" placeholder="Specify inputs or field markers..." class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Cost Tracking (If Any)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" step="0.01" wire:model="amount" placeholder="0.00" class="col-span-2 text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500">
                            <select wire:model="currency" class="text-sm border-gray-300 rounded-lg p-2 bg-white font-bold text-gray-700 focus:ring-blue-500">
                                <option value="USD">USD ($)</option>
                                <option value="SOS">SOS (Sh.)</option>
                            </select>
                        </div>
                        @if($currency === 'SOS' && $amount > 0)
                        <p class="text-[11px] text-gray-500 mt-1 italic">
                            Automatic accounting sync value: ${{ number_format($amount / 57000, 2) }} USD
                        </p>
                        @endif
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition" @if($activeCycleOptions->isEmpty()) disabled @endif>
                        Save Log Entry
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
