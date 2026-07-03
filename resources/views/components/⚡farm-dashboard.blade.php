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
    public $allFieldOptions;

    // GLOBAL SETTINGS
    public $exchange_rate = 57000; // Live editable exchange rate (1 USD = X SOS)

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
    public $partner_mode = 'existing'; 
    public $new_partner_id;
    public $new_partner_name;
    public $new_partner_phone;
    public $new_partner_type = 'kalagoos';

    // Form 3: Register New Plot State
    public $add_plot_field_id;
    public $add_plot_name;
    public $add_plot_size;

    // Drawer Interaction State
    public $selected_cycle_id = null;
    public $selectedCycleDetails = null;

    // Runs once when component instantiates
    public function mount()
    {
        $this->refreshDashboard();
        
        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        }
        if ($this->allPlotOptions->isNotEmpty()) {
            $this->new_plot_id = $this->allPlotOptions->first()->id;
        }
        if ($this->allFieldOptions->isNotEmpty()) {
            $this->add_plot_field_id = $this->allFieldOptions->first()->id;
        }
    }

    // Query engine to re-fetch fresh maps
    public function refreshDashboard()
    {
        $this->totalFields = Field::count();
        $this->activeCycles = CropCycle::where('status', 'growing')->count();
        $this->totalExpenses = CropLog::sum('amount_in_usd');
        
        $this->fields = Field::with(['plots.cropCycles.partner', 'plots.cropCycles.logs' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();
        
        $this->activeCycleOptions = CropCycle::where('status', 'growing')->get();
        $this->allPlotOptions = Plot::all();
        $this->partnerOptions = Partner::all();
        $this->allFieldOptions = Field::all();

        if ($this->selected_cycle_id) {
            $this->selectedCycleDetails = CropCycle::with(['plot', 'partner', 'logs' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])->find($this->selected_cycle_id);
        }
    }

    public function openDrawer($cycleId)
    {
        $this->selected_cycle_id = $cycleId;
        $this->refreshDashboard();
    }

    public function closeDrawer()
    {
        $this->selected_cycle_id = null;
        $this->selectedCycleDetails = null;
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

        $calculatedUsd = null;

        if ($this->amount) {
            $calculatedUsd = ($this->currency === 'SOS') ? ($this->amount / max(1, $this->exchange_rate)) : $this->amount;
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

        Plot::find($this->new_plot_id)->update(['status' => 'active']);

        CropLog::create([
            'cycle_id' => $cycle->id,
            'log_type' => 'progress',
            'title' => 'Crop Cycle Launched',
            'notes' => "System initialized cultivation sequence under structural model: " . strtoupper($this->new_operation_type),
        ]);

        $this->new_crop_name = '';
        $this->new_weather_at_planting = '';
        $this->new_partner_name = '';
        $this->new_partner_phone = '';
        
        $this->refreshDashboard();
        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        }
    }

    // Submit Handler for Form 3: Creating a brand new plot from UI
    public function createPlot()
    {
        $this->validate([
            'add_plot_field_id' => 'required|exists:fields,id',
            'add_plot_name' => 'required|string|max:255',
            'add_plot_size' => 'required|numeric|min:0.01',
        ]);

        Plot::create([
            'field_id' => $this->add_plot_field_id,
            'name' => $this->add_plot_name,
            'size' => $this->add_plot_size,
            'status' => 'fallow', 
        ]);

        $this->add_plot_name = '';
        $this->add_plot_size = '';

        $this->refreshDashboard();
    }

    public function archiveCycle($statusValue)
    {
        if (!$this->selected_cycle_id) return;

        $cycle = CropCycle::find($this->selected_cycle_id);
        $cycle->update(['status' => $statusValue]);

        Plot::find($cycle->plot_id)->update(['status' => 'fallow']);

        CropLog::create([
            'crop_cycle_id' => $cycle->id,
            'log_type' => 'progress',
            'title' => 'Cycle Concluded',
            'notes' => "Production segment status finalized as: " . strtoupper($statusValue) . ". Plot reassigned as fallow.",
        ]);

        $this->closeDrawer();
        $this->refreshDashboard();

        if ($this->activeCycleOptions->isNotEmpty()) {
            $this->crop_cycle_id = $this->activeCycleOptions->first()->id;
        } else {
            $this->crop_cycle_id = null;
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto space-y-6 relative overflow-hidden">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-4 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">60 ha Farm Management</h1>
            <p class="text-gray-500">Shabelle Riverbank • Mogadishu, Somalia</p>
        </div>

        <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-amber-200 flex items-center space-x-3 text-sm">
            <div class="font-bold text-slate-700 uppercase tracking-wider text-xs">Live Market Rate:</div>
            <div class="flex items-center space-x-1.5 font-semibold text-gray-900">
                <span>1 USD =</span>
                <input type="number" wire:model.live="exchange_rate" class="w-24 p-1 text-right text-sm border-gray-300 rounded focus:ring-amber-500 font-bold text-amber-700 bg-slate-50">
                <span class="text-xs text-gray-500">SOS</span>
            </div>
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
                <span class="text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full text-xs font-medium">USD Master Balance</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Sectors & Plot Overview</h2>
                <span class="text-xs text-gray-400 font-medium italic">💡 Click an active card to review full timeline logs</span>
            </div>

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
                    @php
                    $currentActiveCycle = $plot->cropCycles->where('status', 'growing')->first();
                    @endphp

                    <div @if($currentActiveCycle) wire:click="openDrawer({{ $currentActiveCycle->id }})" @endif class="border border-gray-100 rounded-lg p-4 bg-slate-50 flex flex-col justify-between transition relative {{ $currentActiveCycle ? 'cursor-pointer hover:border-blue-400 hover:bg-blue-50/20 shadow-sm' : '' }}">
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
                            @if($plot->status === 'active' && $currentActiveCycle)
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Current Crop:</span>
                                    <span class="font-bold text-blue-700">{{ $currentActiveCycle->crop_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Model Split:</span>
                                    <span class="font-semibold text-gray-700 capitalize">{{ $currentActiveCycle->operation_type }} ({{ intval($currentActiveCycle->owner_profit_share) }}%)</span>
                                </div>
                                @if($currentActiveCycle->partner)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Linked Partner:</span>
                                    <span class="text-gray-700 font-medium">{{ $currentActiveCycle->partner->name }}</span>
                                </div>
                                @endif

                                <div class="flex justify-between pt-2 mt-2 border-t border-gray-200/60 font-semibold text-slate-700">
                                    <span class="text-gray-500 font-normal">Plot Cost:</span>
                                    <span class="text-blue-600">${{ number_format($currentActiveCycle->logs->sum('amount_in_usd'), 2) }}</span>
                                </div>
                            </div>
                            @else
                            <p class="text-gray-400 italic">Fallow field space. Ready for activation.</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm col-span-2">No individual plots defined inside this sector block.</p>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>

        <div class="space-y-6">

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-md font-bold text-slate-700 border-b border-gray-100 pb-2 mb-4">Register New Land Plot</h3>

                <form wire:submit.prevent="createPlot" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Parent Sector (Field)</label>
                        <select wire:model="add_plot_field_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500">
                            @foreach($allFieldOptions as $fOpt)
                            <option value="{{ $fOpt->id }}">{{ $fOpt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Plot Identifier/Name</label>
                        <input type="text" wire:model="add_plot_name" placeholder="e.g., Plot C - Okra Block" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Size (Hectares)</label>
                        <input type="number" step="0.01" wire:model="add_plot_size" placeholder="e.g., 1.25" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition">
                        ➕ Save New Plot Allocation
                    </button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-md font-bold text-slate-700 border-b border-gray-100 pb-2 mb-4">Launch New Crop Cycle</h3>

                <form wire:submit.prevent="launchCycle" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Target Land Space</label>
                        <select wire:model="new_plot_id" class="w-full text-sm border-gray-300 rounded-lg p-2 bg-slate-50 focus:ring-blue-500">
                            @foreach($allPlotOptions as $pOption)
                            <option value="{{ $pOption->id }}">{{ $pOption->name }} ({{ strtoupper($pOption->status) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Crop Classification</label>
                        <input type="text" wire:model="new_crop_name" placeholder="e.g., Watermelon, Okra, Beetroot" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
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
                        <input type="text" wire:model="new_weather_at_planting" placeholder="e.g., Sunny, 32°C, Dry" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500">
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
                        <input type="text" wire:model="title" placeholder="e.g., Drip line check" class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Field Notes</label>
                        <textarea wire:model="notes" rows="2" placeholder="Specify rates or observations..." class="w-full text-sm border-gray-300 rounded-lg p-2 focus:ring-blue-500"></textarea>
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
                            Automatic accounting sync value: ${{ number_format($amount / max(1, (float)$exchange_rate), 2) }} USD
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

    @if($selected_cycle_id && $selectedCycleDetails)
    <div class="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-xs">
        <div class="absolute inset-0" wire:click="closeDrawer"></div>

        <div class="relative w-full max-w-md bg-white h-full shadow-2xl p-6 flex flex-col justify-between z-10 animate-slide-in">
            <div>
                <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-4">
                    <div>
                        <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md uppercase tracking-wider">
                            {{ $selectedCycleDetails->operation_type }} Cycle
                        </span>
                        <h3 class="text-xl font-bold text-slate-800 mt-1">{{ $selectedCycleDetails->crop_name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Location: {{ $selectedCycleDetails->plot->name }}</p>
                    </div>
                    <button wire:click="closeDrawer" class="text-gray-400 hover:text-gray-600 text-lg font-bold p-1">&times;</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-lg border border-gray-200/60 space-y-1.5 text-xs text-slate-700 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Planting Date:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($selectedCycleDetails->planting_date)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Weather Context:</span>
                        <span class="font-medium italic text-gray-600">"{{ $selectedCycleDetails->weather_at_planting }}"</span>
                    </div>
                    @if($selectedCycleDetails->partner)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Partner Profile:</span>
                        <span class="font-semibold text-slate-800">{{ $selectedCycleDetails->partner->name }} ({{ $selectedCycleDetails->partner->phone }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Your Retained Share:</span>
                        <span class="font-semibold text-green-700">{{ intval($selectedCycleDetails->owner_profit_share) }}% Profit Share</span>
                    </div>
                    @endif
                </div>

                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Agronomic Field Timeline</h4>
                <div class="space-y-4 overflow-y-auto max-h-[50vh] pr-1">
                    @forelse($selectedCycleDetails->logs as $log)
                    @php
                    $badgeColor = match($log->log_type) {
                    'infection' => 'bg-red-100 border-red-200 text-red-800',
                    'fertilizer' => 'bg-green-100 border-green-200 text-green-800',
                    'treatment' => 'bg-purple-100 border-purple-200 text-purple-800',
                    'harvest' => 'bg-amber-100 border-amber-200 text-amber-800',
                    default => 'bg-blue-100 border-blue-200 text-blue-800'
                    };
                    @endphp
                    <div class="relative pl-4 border-l-2 border-gray-200 pb-1">
                        <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-gray-300"></div>

                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold border px-1.5 py-0.2 rounded-sm {{ $badgeColor }}">
                                    {{ $log->log_type }}
                                </span>
                                <h5 class="font-semibold text-sm text-slate-800 mt-1">{{ $log->title }}</h5>
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if($log->notes)
                        <p class="text-xs text-gray-600 bg-slate-50/50 p-1.5 rounded-md mt-1 italic border border-gray-100/50">
                            {{ $log->notes }}
                        </p>
                        @endif
                        @if($log->amount)
                        <p class="text-xs font-semibold text-slate-700 mt-1">
                            Cost Tracked: <span class="text-blue-600">${{ number_format($log->amount_in_usd, 2) }} USD</span>
                            @if($log->currency === 'SOS') <span class="text-[10px] text-gray-400 font-normal">({{ number_format($log->amount) }} SOS)</span> @endif
                        </p>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 italic text-center py-4">No logged history items recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4 bg-white mt-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Conclude Production Season</label>
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="archiveCycle('harvested')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-3 rounded-lg text-xs shadow-xs transition text-center">
                        🎉 Record Successful Harvest
                    </button>
                    <button wire:click="archiveCycle('failed')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-lg text-xs shadow-xs transition text-center">
                        ⚠️ Mark Crop Cycle Failed
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endif

</div>
