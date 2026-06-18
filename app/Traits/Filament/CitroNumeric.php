<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Exception;

trait CitroNumeric
{
    /**
     * Standardized Numeric Intelligence for Citroroso Imports.
     * Handles:
     * - Formulas (e.g., "=498+35")
     * - Indonesian format (dots as thousands)
     * - Messy currency symbols
     */
    protected function cleanNumeric($value): int
    {
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $strValue = trim((string) $value);
        if (empty($strValue)) {
            return 0;
        }

        // 1. HANDLE FORMULAS (e.g., "=498+35")
        if (str_starts_with($strValue, '=')) {
            $expr = substr($strValue, 1);
            // Safety check: only allow digits and arithmetic operators
            if (preg_match('/^[0-9\+\-\*\/\.\(\)\s]+$/', $expr)) {
                try {
                    // Optimized simple addition handler (most common case in SOSTEL bug)
                    if (str_contains($expr, '+') && ! str_contains($expr, '(')) {
                        return (int) array_sum(array_map('trim', explode('+', $expr)));
                    }

                    // Fallback to BCMath or numeric extraction if too complex
                } catch (Exception $e) {
                    // Fall through
                }
            }
        }

        // 2. HANDLE INDONESIAN FORMAT & SYMBOLS
        // Remove dots (thousands) and replace commas with dots (decimals)
        $clean = str_replace('.', '', $strValue);
        $clean = str_replace(',', '.', $clean);

        // Remove non-numeric noise (Rp, currency, extra spaces)
        $clean = str_replace(['Rp', ' '], '', $clean);

        // 3. SECURE EXTRACTION
        // We take ONLY the first numeric block to prevent "Concatenation Bug"
        // (e.g. from "533 blabla 10" we take 533)
        if (preg_match('/(\d+(\.\d+)?)/', $clean, $matches)) {
            return (int) round((float) $matches[1]);
        }

        return 0;
    }

    /**
     * Clean numeric input specifically for floating point prices
     */
    protected function cleanFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $strValue = trim((string) $value);
        $clean = str_replace('.', '', $strValue);
        $clean = str_replace(',', '.', $clean);
        $clean = str_replace(['Rp', ' '], '', $clean);

        if (preg_match('/(\d+(\.\d+)?)/', $clean, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }
}

