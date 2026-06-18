{{-- Produk List Column View for Produsen Resource --}}
@php
    $record = $getRecord();
    $produks = $record->produks ?? collect([]);
    $total = $produks->count();
    $limit = 1;
    $displayItems = $produks->take($limit);
    $remaining = $total - $limit;
@endphp

<div class="flex flex-col gap-1">
    @foreach($displayItems as $produk)
        <span class="text-xs font-medium">{{ $produk->nama }}</span>
    @endforeach
    
    @if($remaining > 0)
        <span class="text-xs text-primary cursor-pointer underline decoration-dotted" 
              onclick="document.getElementById('produk-list-{{ $record->id }}').classList.toggle('hidden')">
            +{{ $remaining }} lainnya
        </span>
        
        <div id="produk-list-{{ $record->id }}" class="hidden mt-1 p-2 bg-gray-50 rounded text-xs">
            @foreach($produks->skip($limit) as $produk)
                <div class="py-0.5">{{ $produk->nama }}</div>
            @endforeach
        </div>
    @endif
    
    @if($total === 0)
        <span class="text-xs text-gray-400">-</span>
    @endif
</div>
