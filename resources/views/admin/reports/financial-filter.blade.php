@include('admin.reports.report-style')

<div class="mb-6">
    <x-hhr-toolbar>
        <x-slot:filters>
            <div class="hhr-group">
                <span class="hhr-label-ghost">Bulan</span>
                <select name="month" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    @foreach($months as $k => $v)
                        <option value="{{ $k }}" {{ $month == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="hhr-group">
                <span class="hhr-label-ghost">Tahun</span>
                <select name="year" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    @foreach($years as $k => $v)
                        <option value="{{ $k }}" {{ $year == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:filters>
        
        <x-slot:search>
            <div class="hhr-group px-4 border-l border-white/5 pl-4">
                <h3 class="text-sm font-black tracking-tight uppercase text-white/80 m-0 p-0 leading-none">{{ $months[$month] }} {{ $year }}</h3>
            </div>
        </x-slot:search>
        
        <x-slot:actions>
            <a href="{{ route('admin.report.financial.export', ['month' => $month, 'year' => $year]) }}" class="hhr-btn hhr-btn-excel" title="Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3 -3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                <span class="hidden md:inline">Excel</span>
            </a>
        </x-slot:actions>
    </x-hhr-toolbar>
</div>
