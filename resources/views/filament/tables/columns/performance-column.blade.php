{{-- Performance Column View - 90 Day Performance Badge --}}
@php
    use App\Traits\Filament\HasNuclearPrefetch;
    
    $trait = new class { use HasNuclearPrefetch; };
    $recordId = $getRecord()->getKey();
    $targetType = 'pedagang'; // Default for PedagangResource
    
    // Check if this is for Produsen viewing Pedagang
    if (isset($livewire) && method_exists($livewire, 'getResource') && str_contains($livewire::class, 'Produsen')) {
        $targetType = 'pedagang';
    }
    
    $perf = $trait->getNuclearPerformance($recordId, $targetType);
    $percent = $perf['percent'];
    $laku = $perf['laku'];
    $titip = $perf['titip'];
    
    $color = match (true) {
        $percent > 85 => 'success',
        $percent >= 60 => 'warning',
        default => 'danger',
    };
    
    $title = "90 Hari: {$percent}% ({$laku}/{$titip})";
@endphp

<div class="flex items-center justify-center" title="{{ $title }}">
    @if($titip > 0)
        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded"
              style="min-width: 45px; font-size: 10px; font-weight: 800; border-radius: 4px;
                     text-transform: uppercase; text-align: center;
                     background-color: var(--{{ $color }}-100, #dcfce7);
                     color: var(--{{ $color }}-700, #15803d);">
            {{ $percent }}%
        </span>
    @else
        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded"
              style="min-width: 45px; font-size: 9px;
                     background-color: #f1f5f9;
                     color: #64748b;">
            -
        </span>
    @endif
</div>
