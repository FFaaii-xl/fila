{{-- Last Deposit Column View - Terakhir Titip Badge --}}
@php
    use App\Traits\Filament\HasNuclearPrefetch;
    use Carbon\Carbon;
    
    $recordId = $getRecord()->getKey();
    $targetType = 'pedagang'; // Default for PedagangResource
    
    // Get last deposit data
    $trait = new class { use HasNuclearPrefetch; };
    $data = $trait->getNuclearPerfData($targetType);
    $row = $data[$recordId] ?? null;
    $lastDate = $row->last_date ?? null;
    
    // Calculate relative date
    $label = '';
    $color = '';
    
    if ($lastDate) {
        $tz = config('app.timezone', 'Asia/Jakarta');
        $date = Carbon::parse($lastDate, $tz)->startOfDay();
        $today = now($tz)->startOfDay();
        $diff = (int) $date->diffInDays($today);
        
        $label = match (true) {
            $diff === 0 => 'Hari Ini',
            $diff === 1 => 'Kemarin',
            default => "{$diff} H",
        };
        
        $color = match (true) {
            $diff <= 1 => 'success',
            $diff <= 6 => 'warning',
            default => 'danger',
        };
        
        $fullDate = date('d M Y', strtotime($lastDate));
        $title = "Terakhir Titip: {$fullDate}";
    } else {
        $label = '-';
        $title = 'Belum pernah titip';
        $color = 'gray';
    }
@endphp

<div class="flex items-center justify-center" title="{{ $title }}">
    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded"
          style="min-width: 55px; font-size: 9px; font-weight: 800; border-radius: 4px;
                 text-transform: uppercase; text-align: center; cursor: help;
                 @if($color === 'success')
                     background-color: #dcfce7; color: #15803d;
                 @elseif($color === 'warning')
                     background-color: #fef3c7; color: #b45309;
                 @elseif($color === 'danger')
                     background-color: #fee2e2; color: #dc2626;
                 @else
                     background-color: #f1f5f9; color: #64748b;
                 @endif">
        {{ $label }}
    </span>
</div>
