{{-- Reusable Filter Bar Component for Reports --}}
{{-- Sticky filter bar with date range and other filters --}}

@props([
    'showDateRange' => true,
    'showMonthYear' => false,
    'showPedagangFilter' => false,
    'pedagangList' => [],
])

<div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
    <div class="px-4 py-4">
        {{ $slot }}
    </div>
    
    {{-- Filter Controls --}}
    <div class="px-4 pb-4">
        {{-- Mode Tabs --}}
        @if(isset($modes))
            <div class="flex gap-2 mb-3 overflow-x-auto">
                @foreach($modes as $modeKey => $modeLabel)
                    <button wire:click="$set('mode', '{{ $modeKey }}')" 
                        class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors
                        {{ $mode === $modeKey ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700' }}">
                        {{ $modeLabel }}
                    </button>
                @endforeach
            </div>
        @endif
        
        {{-- Date Inputs based on mode --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($showDateRange && $mode === 'range')
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal Mulai</label>
                    <input type="date" wire:model.live="dateStart" 
                        class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal Selesai</label>
                    <input type="date" wire:model.live="dateEnd" 
                        class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
            @endif
            
            @if($showDateRange && $mode === 'tanggal')
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pilih Tanggal</label>
                    <input type="date" wire:model.live="selectedDate" 
                        class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
            @endif
            
            @if($showMonthYear)
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan</label>
                    <select wire:model.live="month" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ \Carbon\Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tahun</label>
                    <select wire:model.live="year" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                        @foreach(range(now()->year - 2, now()->year) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            {{-- Pedagang Filter --}}
            @if($showPedagangFilter && count($pedagangList) > 0)
                <div class="col-span-2 md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Filter Pedagang</label>
                    <select wire:model.live="pedagangId" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                        <option value="">Semua Pedagang</option>
                        @foreach($pedagangList as $pdk)
                            <option value="{{ $pdk->id ?? $pdk['id'] ?? $pdk }}">{{ $pdk->nama ?? $pdk['nama'] ?? $pdk }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>
</div>
