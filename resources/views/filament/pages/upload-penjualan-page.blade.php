<x-filament-panels::page>
    <div class="kinetic-wrapper">
        @if (session()->has('upload_manifest_errors'))
            <x-filament::badge color="danger" class="mb-4">
                {!! implode('<br>', session('upload_manifest_errors')) !!}
            </x-filament::badge>
        @endif

        @if (session()->has('error'))
            <x-filament::badge color="danger" class="mb-4">
                {{ session('error') }}
            </x-filament::badge>
        @endif

        @if (session()->has('success'))
            <x-filament::badge color="success" class="mb-4">
                {{ session('success') }}
            </x-filament::badge>
        @endif

        <!-- Includes old custom header styles/logic -->
        @include('admin.upload.partials.header', [
            'deadlineTime' => $deadlineTime,
            'deadlineActive' => $deadlineActive,
            'serverTime' => $serverTime,
            'isExempt' => $isExempt,
            'roleLabel' => $roleLabel,
            'modeLabel' => $modeLabel,
            'pedagangId' => $pedagangId,
            'tanggal' => $tanggal,
            'isLocked' => $isLocked,
            'lockError' => $lockError,
        ])

        @if($pedagangId)
            <!-- DRAFT EDITOR MODE -->
            @include('admin.reports.draft-editor', [
                'pedagang' => $pedagang,
                'initialItems' => $items,
                'products' => $allProducts,
                'selectedDate' => $tanggal,
                'isSpecial' => false,
                'history' => $history,
                'currentVersion' => $currentVersion,
                'hasChanges' => $hasChanges,
                'isAdmin' => $isAdmin,
                'uploadForm' => '', // TODO: Filament specific upload form
                'downloadButton' => '', // TODO: Filament action
                'pullButton' => '', // TODO: Filament action
                'isLocked' => $isLocked,
                'lockError' => $lockError,
            ])
        @else
            <!-- LIBRARY MODE -->
            @include('admin.reports.report-style')
            <div class="space-y-2">
                @include('admin.upload.partials.hub-cards', [
                    'isPengurus' => $isPengurus,
                    'uploadForm' => '', // TODO: Filament specific upload form
                    'downloadButton' => '', // TODO: Filament action
                    'pullButton' => '',
                    'hasChanges' => $hasChanges,
                    'notSent' => $notSentMerchants,
                    'sent' => $sentMerchants,
                    'totalLaku' => $sentMerchants->sum('total_laku'),
                    'totalOmset' => $sentMerchants->sum('total_omset'),
                    'totalLaba' => $sentMerchants->sum('total_laba'),
                    'globalEfficiency' => $sentMerchants->sum('total_titip') > 0 ? round(($sentMerchants->sum('total_laku') / $sentMerchants->sum('total_titip')) * 100) : 0,
                    'date' => $tanggal,
                ])

                <!-- TODO: Insert Filament Table here to replace TableBuilderHtml -->
                <div class="mt-8">
                    <x-filament::badge color="info">The data table implementation is being migrated to Filament's native format.</x-filament::badge>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
