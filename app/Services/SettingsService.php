<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SettingsService
{
    protected array $defaults = [];
    protected string $overridePath;

    public function __construct()
    {
        $this->defaults = config('citroroso', []);
        $this->overridePath = storage_path('app/settings.json');
    }

    public function all(): array
    {
        $settings = $this->defaults;
        $overrides = $this->loadOverrides();
        foreach ($overrides as $key => $value) {
            $settings[$key] = $value;
        }
        return $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $settings;
            foreach ($keys as $k) {
                if (!is_array($value) || !array_key_exists($k, $value)) {
                    return $default;
                }
                $value = $value[$k];
            }
            return $value;
        }
        return $settings[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $overrides = $this->loadOverrides();
        $overrides[$key] = $value;
        $this->saveOverrides($overrides);
    }

    public function setMany(array $settings): void
    {
        $overrides = $this->loadOverrides();
        foreach ($settings as $key => $value) {
            $overrides[$key] = $value;
        }
        $this->saveOverrides($overrides);
    }

    public function getKasPedagang(float $modal): float
    {
        $ranges = $this->get('kas_pedagang_ranges', []);
        if (empty($ranges)) {
            return 1500;
        }
        usort($ranges, fn ($a, $b) => ($b['min'] ?? 0) - ($a['min'] ?? 0));
        foreach ($ranges as $range) {
            if ($modal >= ($range['min'] ?? 0)) {
                return (float) ($range['fee'] ?? 1500);
            }
        }
        return 1500;
    }

    protected function loadOverrides(): array
    {
        if (!File::exists($this->overridePath)) {
            return [];
        }
        try {
            $content = File::get($this->overridePath);
            $data = json_decode($content, true);
            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function saveOverrides(array $overrides): void
    {
        $dir = dirname($this->overridePath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $json = json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        File::put($this->overridePath, $json);
    }
}
