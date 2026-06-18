<?php

namespace App\Traits;

use App\Services\SettingsService;

trait MerchantFinancialRules
{
    public function calculateMerchantProup(float $modal, int $quantity, string $merchantName): float
    {
        $settings = app(SettingsService::class);
        $excludedMerchants = $settings->get('special_merchant_list', []);

        foreach ($excludedMerchants as $excluded) {
            if (trim(strtolower($merchantName)) === trim(strtolower($excluded))) {
                return 0.0;
            }
        }

        $threshold = (int) $settings->get('product_threshold_count', 30);
        $rate = (float) $settings->get('product_rate', 0.015);

        if ($quantity > $threshold) {
            return floor(($modal * $rate) / 1000) * 1000;
        }

        return 0.0;
    }

    public function getAdjustedMerchantModal(float $modal, int $quantity, string $merchantName): float
    {
        return $modal + $this->calculateMerchantProup($modal, $quantity, $merchantName);
    }

    public function getTieredMerchantKas(float $modal): float
    {
        return (float) app(SettingsService::class)->getKasPedagang($modal);
    }
}
