<?php

use Livewire\Component;
use App\Models\Field;
use App\Models\CropCycle;
use App\Models\CropLog;

new class extends Component {
    public $totalFields;
    public $activeCycles;
    public $totalExpenses;
    public $fields;

    // This runs automatically when the component loads
    public function mount()
    {
        $this->totalFields = Field::count();
        $this->activeCycles = CropCycle::where('status', 'growing')->count();
        $this->totalExpenses = CropLog::sum('amount_in_usd');
        $this->fields = Field::with('plots.cropCycles.partner')->get();
    }
}; ?>

<!-- Everything below this line is your HTML frontend styled with Tailwind -->
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
                <span class="text-4xl font-bold text-gray-900">{{ $activeCycles }}</span>
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

    <!-- Main Land Mapping Sections -->
    <div class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800">Sectors & Plot Overview</h2>

        @foreach($fields as $field)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Field Row Head -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-wrap justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-green-800">{{ $field->name }}</h3>
                    <p class="text-xs text-gray-500">GPS Coordinates: {{ $field->latitude ?? 'N/A' }}, {{ $field->longitude ?? 'N/A' }}</p>
                </div>
                <span class="bg-green-600 text-white text-xs px-3 py-1 rounded-full font-semibold">
                    {{ $field->total_area }} Hectares
                </span>
            </div>

            <!-- Inner Plots Grid -->
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

                    <!-- Show active partnership cycle if one exists -->
                    <div class="mt-4 pt-3 border-t border-gray-200/60 text-xs">
                        @if($plot->status === 'active' && $plot->cropCycles->first())
                        @php $activeCycle = $plot->cropCycles->first(); @endphp
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Current Crop:</span>
                                <span class="font-bold text-blue-700">{{ $activeCycle->crop_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Model Structure:</span>
                                <span class="font-semibold text-gray-700 capitalize">{{ $activeCycle->operation_type }}</span>
                            </div>
                            @if($activeCycle->partner)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Managing Partner:</span>
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
</div>
