<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use MoonShine\Support\Enums\ToastType;

class SettingsController extends Controller
{
    public function save(Request $request, SettingsService $settingsService)
    {
        $data = $request->all();

        // Process special_merchant_list from string to array
        if (isset($data['special_merchant_list'])) {
            $data['special_merchant_list'] = array_map('trim', explode(',', $data['special_merchant_list']));
        }

        // Filter and sanitize other data if needed
        $cleanData = [
            'transaction_threshold' => (int) ($data['transaction_threshold'] ?? 10000),
            'kas_produsen_flat' => (int) ($data['kas_produsen_flat'] ?? 1500),
            'kas_threshold' => (int) ($data['kas_threshold'] ?? 50000),
            'proup_rate' => (float) ($data['proup_rate'] ?? 0.015),
            'proup_threshold_count' => (int) ($data['proup_threshold_count'] ?? 30),
            'special_merchant_list' => $data['special_merchant_list'] ?? [],
            'kas_pedagang_ranges' => array_values($data['kas_pedagang_ranges'] ?? []),
            'submission_deadline_active' => (bool) ($data['submission_deadline_active'] ?? false),
            'submission_deadline_time' => $data['submission_deadline_time'] ?? '14:00',
        ];

        $settingsService->setMany($cleanData);

        return back()->with('toast', [
            'type' => ToastType::SUCCESS->value,
            'message' => 'Pengaturan berhasil disimpan',
        ]);
    }
}
