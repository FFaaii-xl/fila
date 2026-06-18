<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaksi;
use Illuminate\Support\Facades\Storage;

final class BackupFooterService
{
    private const BASE_PATH = 'backup/footer-nota';

    public function record(Transaksi $transaksi, int $pembulatan, int $carryOver = 0, string $event = 'settlement', ?int $payRound = null): ?string
    {
        $date = $this->normalizeDate((string) $transaksi->tanggal);
        $snapshot = $this->decodeSnapshot((string) ($transaksi->keterangan ?? ''));
        $bayar = (float) ($snapshot['bruto'] ?? $transaksi->jumlah ?? 0);
        $kas = (float) ($transaksi->kas ?? 0);
        $tabungan = (float) ($snapshot['tabungan'] ?? 0);
        $lain = (float) ($snapshot['lain'] ?? 0);

        $payload = [
            'date' => $date,
            'event' => $event,
            'created_at' => now()->toDateTimeString(),
            'transaksi' => [
                'id' => $transaksi->id,
                'tanggal' => $date,
                'owner_type' => $transaksi->owner_type,
                'owner_id' => $transaksi->owner_id,
                'owner_name' => $transaksi->owner?->nama,
            ],
            'values' => [
                'bayar' => $bayar,
                'kas' => $kas,
                'tabungan' => $tabungan,
                'lain' => $lain,
                'kemarin' => (float) ($transaksi->kemarin ?? 0),
                'pembulatan' => $pembulatan,
                'carry_over' => $carryOver,
                'jumlah' => (float) $transaksi->jumlah,
            ],
        ];

        $payRound = $payRound ?? $this->getPayRound($date);

        if ($payRound > 1) {
            $prevPayRound = $payRound - 1;
            if ($this->isDataIdentical($date, $prevPayRound, $payload)) {
                return null;
            }
        }

        $path = $this->write($date, $payRound, $payload);
        $this->cleanupOldFolder($date);

        return $path;
    }

    private function getPayRound(string $date): int
    {
        $folder = self::BASE_PATH;
        
        if (!Storage::disk('local')->exists($folder)) {
            return 1;
        }

        $maxPayRound = 0;
        
        for ($round = 1; $round <= 10; $round++) {
            $filePath = "{$folder}/{$date}-pay{$round}.json";
            if (Storage::disk('local')->exists($filePath)) {
                $maxPayRound = $round;
            }
        }

        return $maxPayRound + 1;
    }

    private function isDataIdentical(string $date, int $payRound, array $newPayload): bool
    {
        $prevFilePath = self::BASE_PATH . "/{$date}-pay{$payRound}.json";
        
        if (!Storage::disk('local')->exists($prevFilePath)) {
            return false;
        }

        $prevContent = Storage::disk('local')->get($prevFilePath);
        $prevData = json_decode($prevContent, true);

        if (!$prevData || !isset($prevData['transactions'])) {
            return false;
        }

        $prevTx = $prevData['transactions'][0] ?? null;
        if (!$prevTx) {
            return false;
        }

        if ($prevTx['transaksi']['id'] !== $newPayload['transaksi']['id']) {
            return false;
        }

        if ($prevTx['values'] !== $newPayload['values']) {
            return false;
        }

        return true;
    }

    private function write(string $date, int $payRound, array $payload): string
    {
        $folder = self::BASE_PATH;
        
        if (!Storage::disk('local')->exists($folder)) {
            Storage::disk('local')->makeDirectory($folder);
        }

        $filePath = "{$folder}/{$date}-pay{$payRound}.json";
        
        $existingData = [];
        if (Storage::disk('local')->exists($filePath)) {
            $content = Storage::disk('local')->get($filePath);
            $existingData = json_decode($content, true) ?? [];
        }

        if (!isset($existingData['transactions'])) {
            $existingData = [
                'date' => $date,
                'pay_round' => $payRound,
                'created_at' => now()->toDateTimeString(),
                'transactions' => [],
            ];
        }

        $existingData['transactions'][] = $payload;
        $existingData['updated_at'] = now()->toDateTimeString();

        Storage::disk('local')->put(
            $filePath,
            json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $filePath;
    }

    private function cleanupOldFolder(string $date): void
    {
        $oldFolder = 'backup/pembulatan/' . $date;
        
        if (Storage::disk('local')->exists($oldFolder)) {
            $files = Storage::disk('local')->files($oldFolder);
            foreach ($files as $file) {
                Storage::disk('local')->delete($file);
            }
            try {
                Storage::disk('local')->deleteDirectory($oldFolder);
            } catch (\Exception $e) {
                // Ignore
            }
        }
    }

    private function normalizeDate(string $date): string
    {
        return date('Y-m-d', strtotime($date));
    }

    private function decodeSnapshot(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getBackupPath(string $date, int $payRound = 1): ?string
    {
        $filePath = self::BASE_PATH . "/{$date}-pay{$payRound}.json";
        
        if (Storage::disk('local')->exists($filePath)) {
            return $filePath;
        }

        return null;
    }

    public function getBackupData(string $date, int $payRound = 1): ?array
    {
        $filePath = $this->getBackupPath($date, $payRound);
        
        if (!$filePath) {
            return null;
        }

        $content = Storage::disk('local')->get($filePath);
        return json_decode($content, true);
    }

    public function listBackups(string $date): array
    {
        $backups = [];
        
        for ($round = 1; $round <= 10; $round++) {
            $filePath = self::BASE_PATH . "/{$date}-pay{$round}.json";
            if (Storage::disk('local')->exists($filePath)) {
                $backups[] = [
                    'pay_round' => $round,
                    'path' => $filePath,
                    'modified' => Storage::disk('local')->lastModified($filePath),
                ];
            }
        }

        return $backups;
    }
}
