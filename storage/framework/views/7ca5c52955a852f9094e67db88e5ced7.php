
<?php echo $__env->make('filament.components.report-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div x-data="{ allOpen: <?php echo e($mode === 'tanggal' ? 'true' : 'false'); ?>, searchQuery: '' }" @toggle-all.window="allOpen = $event.detail.state" class="space-y-5">
    
    <div class="hhr-toolbar">
        
        <div class="hhr-group">
            <span class="hhr-label-ghost hidden sm:flex">Mode</span>
            <button wire:click="$set('mode', 'tanggal')" 
                class="hhr-btn <?php echo e($mode === 'tanggal' ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : ''); ?>">
                Harian
            </button>
            <button wire:click="$set('mode', 'nama')" 
                class="hhr-btn <?php echo e($mode === 'nama' ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : ''); ?>">
                Bulanan
            </button>
            <button wire:click="$set('mode', 'tahunan')" 
                class="hhr-btn <?php echo e($mode === 'tahunan' ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : ''); ?>">
                Tahunan
            </button>
            <button wire:click="$set('mode', 'range')" 
                class="hhr-btn <?php echo e($mode === 'range' ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : ''); ?>">
                Range
            </button>
        </div>

        
        <div class="hhr-group">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($mode === 'tanggal'): ?>
                <span class="hhr-label-ghost hidden sm:flex">Tgl</span>
                <input type="date" wire:model.live="selectedDate" class="form-input">
            <?php elseif($mode === 'range'): ?>
                <span class="hhr-label-ghost hidden sm:flex text-[9px]">Mulai</span>
                <input type="date" wire:model.live="dateStart" class="form-input">
                <span class="hhr-label-ghost hidden sm:flex text-[9px]">Sampai</span>
                <input type="date" wire:model.live="dateEnd" class="form-input">
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($mode === 'nama' || $mode === 'tahunan'): ?>
        <div class="hhr-group">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($mode === 'nama'): ?>
                <select wire:model.live="selectedMonth" class="form-select">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = range(1, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e(str_pad($m, 2, '0', STR_PAD_LEFT)); ?>">
                            <?php echo e(\Carbon\Carbon::create(2024, $m, 1)->format('M')); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <select wire:model.live="selectedYear" class="form-select">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = range(now()->year - 2, now()->year); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isProdusenOnlyMode && count($produsenList) > 0): ?>
        <div class="hhr-group">
            <select wire:model.live="selectedProdusen" class="form-select" style="max-width: 140px;">
                <option value="">Semua Produsen</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $produsenList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $psn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($psn->id); ?>"><?php echo e($psn->nama); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div class="hhr-search-kinetic">
            <div class="relative w-full">
                <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <input type="text" x-model="searchQuery" @input="filterGroups()" placeholder="Cari..." 
                    class="form-input w-full pl-8" style="padding-left: 2.2rem !important;">
            </div>
        </div>

        
        <div class="hhr-action-group">
            <button @click.prevent="allOpen = !allOpen; $dispatch('toggle-all', { state: allOpen })" class="hhr-btn opacity-60 hover:opacity-100" :title="allOpen ? 'Tutup Semua' : 'Buka Semua'">
                <svg x-show="!allOpen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                <svg x-show="allOpen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/></svg>
            </button>
            <button class="hhr-btn hhr-btn-excel" title="Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3 -3v-1m-4-4-4 4m0 0-4-4m4 4V4"/>
                </svg>
                <span class="hidden md:inline font-sans">Export</span>
            </button>
            <button onclick="window.print()" class="hhr-btn opacity-60 hover:opacity-100" title="Cetak Laporan">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
            </button>
        </div>
    </div>

    
    <?php 
        $globalPerc = ($totals['titip'] ?? 0) > 0 ? round((($totals['laku'] ?? 0) / ($totals['titip'] ?? 1)) * 100, 1) : 0;
        $countLabel = $isProdusenOnlyMode ? 'Produk' : 'Produsen';
        $countValue = is_array($groupedData) ? count($groupedData) : $groupedData->count();
    ?>
    <div class="glass-pill py-1.5 px-5 sm:px-6 flex items-center justify-center gap-4 sm:gap-6 whitespace-nowrap overflow-x-auto no-scrollbar shadow-2xl transition-all duration-500 hover:bg-slate-900/60 relative">
        <div class="flex items-center gap-6 sm:gap-8 font-mono-numbers">
            
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs"><?php echo e($countLabel); ?></span>
                <span class="text-xs font-bold text-violet-400 tracking-tight"><?php echo e($countValue); ?></span>
            </div>
            <div class="w-px h-6 bg-white/10 hidden sm:block"></div>
            
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Titipan</span>
                <span class="text-xs font-bold text-blue-400 opacity-80 tracking-tight"><?php echo e(number_format($totals['titip'] ?? 0)); ?></span>
            </div>
            
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Terjual</span>
                <span class="text-xs font-bold text-emerald-400 tracking-tight"><?php echo e(number_format($totals['laku'] ?? 0)); ?></span>
            </div>
            
            <div class="flex flex-col gap-1 text-center min-w-[60px]">
                <span class="metric-label-xs">Efisiensi</span>
                <span class="text-xs font-bold tracking-tight" style="color: hsl(<?php echo e($globalPerc * 1.4); ?>, 100%, 50%) !important;"><?php echo e($globalPerc); ?>%</span>
                <div class="w-full h-1 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700" style="width: <?php echo e(min($globalPerc, 100)); ?>%; background: hsl(<?php echo e($globalPerc * 1.4); ?>, 100%, 50%);"></div>
                </div>
            </div>
            <div class="w-px h-6 bg-white/10 hidden sm:block"></div>
            
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Omset Bruto</span>
                <span class="text-xs font-bold tracking-tight text-amber-400">Rp <?php echo e(number_format($totals['omset'] ?? 0, 0, ',', '.')); ?></span>
            </div>
        </div>
        
        <div class="absolute right-4 sm:right-8 flex items-center gap-3">
            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse-critical shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
            <div class="text-[9px] font-mono opacity-20 uppercase tracking-[0.2em] hidden lg:block">
                <?php echo e($mode === 'tanggal' ? 'LIVE' : 'v3.0'); ?>

            </div>
        </div>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 items-start pb-12" id="reportContainer">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $groupedData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php 
            $summary = $group['summary'] ?? [];
            $perc = ($summary['titip'] ?? 0) > 0 ? round(($summary['laku'] ?? 0) / ($summary['titip'] ?? 1) * 100, 1) : 0;
            $hariJualan = $summary['hari_jualan'] ?? 1;
            $isDaily = ($mode === 'tanggal');
        ?>
        
        <div class="group-box glass-panel rounded-xl overflow-hidden" x-data="{ open: <?php echo e($isDaily ? 'true' : 'false'); ?> }" @toggle-all.window="open = $event.detail.state" data-group="<?php echo e(strtolower($key)); ?>">
            
            <div @click="open = !open" class="box-header flex flex-col sm:flex-row sm:items-center justify-between py-2.5 px-4 cursor-pointer hover:bg-white/5 transition-all border-b border-white/5">
                <div class="flex items-center gap-3">
                    <div class="transition-transform duration-300 opacity-30" :class="open ? 'rotate-90' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                    <span class="num-badge num-badge-producer"><?php echo e($loop->iteration); ?></span>
                    <h3 class="font-bold text-[10px] group-title select-none opacity-90 capitalize tracking-wide"><?php echo e(ucwords(strtolower($key))); ?></h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isDaily): ?>
                        <span class="font-mono text-[8px] opacity-20 font-normal bg-white/5 px-1.5 py-0.5 rounded"><?php echo e($hariJualan); ?>D</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                
                <div class="mobile-metric-bar font-mono-numbers mt-1.5 sm:mt-0 flex items-center gap-1.5">
                    <div class="metric-capsule pill-onyx"><span class="metric-label-xs">T</span> <span><?php echo e(number_format($summary['titip'] ?? 0, 0, ',', '.')); ?></span></div>
                    <div class="metric-capsule pill-onyx"><span class="metric-label-xs">L</span> <span class="font-bold text-emerald-400"><?php echo e(number_format($summary['laku'] ?? 0, 0, ',', '.')); ?></span></div>
                    <div class="metric-capsule font-bold min-w-[40px] justify-center" style="color: hsl(<?php echo e($perc * 1.4); ?>, 100%, 50%) !important; background: hsla(<?php echo e($perc * 1.4); ?>, 100%, 50%, 0.08); border-color: hsla(<?php echo e($perc * 1.4); ?>, 100%, 50%, 0.2);">
                        <?php echo e($perc); ?>%
                    </div>
                    <div class="metric-capsule pill-onyx hidden sm:inline-flex"><span class="metric-label-xs">Rp</span> <span class="text-amber-400 opacity-80"><?php echo e(number_format($summary['omset'] ?? 0, 0, ',', '.')); ?></span></div>
                </div>
            </div>
            
            
            <div x-show="open" class="bg-black/20">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($group['products']) && count($group['products']) > 0): ?>
                    
                    <div class="pl-3 sm:pl-8 border-l-2 border-emerald-500/20 ml-4 pb-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group['products']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $productName => $productData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php 
                            $pSummary = $productData['summary'] ?? [];
                            $pPerc = ($pSummary['titip'] ?? 0) > 0 ? round(($pSummary['laku'] ?? 0) / ($pSummary['titip'] ?? 1) * 100, 1) : 0;
                            $pHari = $pSummary['hari_jualan'] ?? 1;
                        ?>
                        <div class="product-group mt-2 mr-3" x-data="{ openPr: false }" @toggle-all.window="openPr = $event.detail.state">
                            <div @click="openPr = !openPr" class="flex flex-col sm:flex-row sm:items-center justify-between py-1.5 px-3 cursor-pointer bg-white/[0.03] hover:bg-white/[0.07] rounded-lg transition-all border border-white/[0.06]">
                                <div class="flex items-center gap-2">
                                    <span class="num-badge num-badge-product"><?php echo e($loop->iteration); ?></span>
                                    <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: hsl(<?php echo e($pPerc * 1.4); ?>, 100%, 50%);"></div>
                                    <span class="text-[9px] font-bold opacity-70 capitalize tracking-wider"><?php echo e(ucwords(strtolower($productName))); ?></span>
                                    <span class="font-mono text-[7px] opacity-20">[<?php echo e($pHari); ?>D]</span>
                                </div>
                                <div class="mobile-metric-bar font-mono-numbers mt-1 sm:mt-0 flex items-center gap-1.5">
                                    <div class="metric-capsule"><span class="metric-label-xs">T</span> <span class="opacity-60"><?php echo e(number_format($pSummary['titip'] ?? 0, 0, ',', '.')); ?></span></div>
                                    <div class="metric-capsule"><span class="metric-label-xs">L</span> <span class="opacity-80 text-emerald-400"><?php echo e(number_format($pSummary['laku'] ?? 0, 0, ',', '.')); ?></span></div>
                                    <div class="metric-capsule pill-onyx font-bold border-transparent text-[9px]" style="color: hsl(<?php echo e($pPerc * 1.4); ?>, 100%, 50%) !important;"><?php echo e($pPerc); ?>%</div>
                                </div>
                            </div>
                            <div x-show="openPr" class="mt-1 bg-black/20 rounded-lg mx-1 overflow-hidden border border-white/5">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $productData['details'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="px-4 py-2.5 flex items-center justify-between text-[11px] border-b border-white/5 last:border-0 hover:bg-white/5 transition-colors">
                                    <span class="text-slate-500 font-mono-numbers min-w-[50px]">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($detail->tgl)): ?>
                                            <?php echo e(\Carbon\Carbon::parse($detail->tgl)->format('d M')); ?>

                                        <?php elseif(isset($detail->bln)): ?>
                                            <?php echo e(\Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F')); ?>

                                        <?php else: ?>
                                            -
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono-numbers text-blue-400 opacity-80"><?php echo e($detail->total_titip ?? 0); ?></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-slate-600"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                                        <span class="font-mono-numbers text-emerald-400"><?php echo e($detail->total_laku ?? 0); ?></span>
                                    </div>
                                    <span class="font-mono-numbers text-amber-400 font-semibold opacity-80">Rp <?php echo e(number_format($detail->total_omset ?? 0, 0, ',', '.')); ?></span>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php elseif(isset($group['details'])): ?>
                    
                    <div class="divide-y divide-white/5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group['details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="px-4 py-2.5 flex items-center justify-between text-[11px] bg-white/[0.01] hover:bg-white/5 transition-colors">
                            <span class="text-slate-400 font-mono-numbers">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($detail->tgl)): ?>
                                    <?php echo e(\Carbon\Carbon::parse($detail->tgl)->format('d M')); ?>

                                <?php elseif(isset($detail->bln)): ?>
                                    <?php echo e(\Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F')); ?>

                                <?php else: ?>
                                    <?php echo e($detail->produsen_nama ?? ''); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <div class="flex items-center gap-4 font-mono-numbers">
                                <span class="text-blue-400 opacity-80"><?php echo e($detail->total_titip ?? 0); ?></span>
                                <span class="text-emerald-400"><?php echo e($detail->total_laku ?? 0); ?></span>
                                <span class="text-amber-400 font-semibold opacity-80">Rp <?php echo e(number_format($detail->total_omset ?? 0, 0, ',', '.')); ?></span>
                            </div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <div class="col-span-full">
            <div class="p-16 text-center opacity-40 italic text-xs tracking-wider border-none shadow-none bg-transparent">
                <div class="editorial-title text-xl mb-3">Hening...</div>
                Data tidak ditemukan di semesta ini.
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>

<script>
    function filterGroups() {
        const query = document.querySelector('[x-model="searchQuery"]').value.toLowerCase();
        const groups = document.querySelectorAll('.group-box');
        
        groups.forEach(group => {
            const name = group.dataset.group || '';
            let hasVisibleProduct = false;
            
            // Check nested products if available
            const products = group.querySelectorAll('.product-group');
            if (products.length > 0) {
                products.forEach(prod => {
                    const prodName = prod.querySelector('.tracking-wider').textContent.toLowerCase();
                    if (prodName.includes(query) || name.includes(query)) {
                        prod.style.display = '';
                        hasVisibleProduct = true;
                    } else {
                        prod.style.display = 'none';
                    }
                });
            }
            
            if (name.includes(query) || hasVisibleProduct) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    }
</script>
<?php /**PATH D:\www\fila\resources\views/filament/pages/producer-sales.blade.php ENDPATH**/ ?>