<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DetailTabungan;
use App\Models\DetailTransaksi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class NotaBackupService
{
    /**
     * Generate backup text matching the exact printed nota layout.
     * Financial summary (Bayar/Kas/Kemarin/Lain/Tabungan/Pembulatan/Uang Hari Ini)
     * only appears on the FIRST nota of each producer.
     */
    public static function backup(Collection $notads, string $date): void
    {
        $text = '';

        foreach ($notads as $nota) {
            $no_nota = $nota['no_nota'];
            $produsen = $nota['produsen'];
            $sections = $nota['sections'];
            $transaksi = $nota['transaksi'];
            $tanggal = date('d/m/Y', strtotime($nota['tanggal']));
            $tanggalShort = date('d-m-y', strtotime($nota['tanggal']));
            $isFirstProduk = $nota['is_first_produk'] ?? false;
            $produsenPrefix = ($produsen->gender === 'female' ? 'B. ' : 'P. ');

            // === HEADER (matches nota-header-table) ===
            $produkNama = strtoupper(self::abbreviate($sections[0]['produk']->nama));
            $multiTag = count($sections) > 1 ? ' (+'.(count($sections) - 1).')' : '';
            $hargaBeli = count($sections) === 1
                ? number_format((float)$sections[0]['produk']->harga_beli, 0, ',', '.')
                : '';
            $bundle = ($produsen->bundle_ke ?? 0) > 0 ? 'B'.$produsen->bundle_ke : '';

            $text .= "{$no_nota} \t {$produkNama}{$multiTag} \t {$tanggal}\n";
            $text .= strtoupper($produsenPrefix.$produsen->nama)." \t {$hargaBeli} \t {$bundle}\n";

            // === SECTIONS (each product table) ===
            foreach ($sections as $sectionIndex => $section) {
                $produk = $section['produk'];
                $items = $section['items'];

                if (count($sections) > 1) {
                    $text .= $produk->nama.' ('.number_format((float)$produk->harga_beli, 0, ',', '.').")\n";
                }

                if ($sectionIndex === 0) {
                    $text .= "NO \t NAMA \t Ttp \t S.Jl \t Rtrn \t Lku \t BYAR\n";
                }

                $no = 1;
                foreach ($items as $item) {
                    $text .= "{$no} \t {$item->p_display_name} \t ".
                        ($item->titip !== 0 ? $item->titip : '')." \t ".
                        ($item->sisa_jual !== 0 ? $item->sisa_jual : '')." \t ".
                        ($item->ret !== 0 ? $item->ret : '')." \t ".
                        ($item->laku !== 0 ? $item->laku : '')." \t ".
                        (($item->f_bayar !== '0' && $item->f_bayar !== 0) ? $item->f_bayar : '')."\n";
                    $no++;
                }

                // Footer per section
                $lakuPercent = $section['sumTitip'] > 0 ? round(($section['sumLaku'] / $section['sumTitip']) * 100) : 0;
                $sumBayarFormatted = $section['sumBayar'] >= 1000000
                    ? number_format($section['sumBayar'] / 1000, 0, ',', '.').'K'
                    : number_format($section['sumBayar'], 0, ',', '.');

                $text .= "Laku {$lakuPercent}% \t {$section['sumTitip']} \t {$section['sumSisaJual']} \t {$section['sumReturn']} \t {$section['sumLaku']} \t {$sumBayarFormatted}\n";
            }

            // === FINANCIAL SUMMARY (only on first nota of this producer) ===
            if ($isFirstProduk) {
                $summary = self::calculateSummary($nota);

                $text .= 'Bayar '.number_format($summary['bayar'], 0, ',', '.')."\n";
                $text .= 'Kas -'.number_format($summary['kas'], 0, ',', '.')."\n";
                $text .= 'Kemarin '.($summary['kemarin'] >= 0 ? '+' : '').number_format($summary['kemarin'], 0, ',', '.')."\n";
                $text .= 'Lain '.($summary['lain'] >= 0 ? '+' : '-').number_format(abs($summary['lain']), 0, ',', '.')."\n";
                $text .= 'Tabungan -'.number_format($summary['tabungan'], 0, ',', '.')."\n";
                $text .= 'Pembulatan '.($summary['pembulatan'] >= 0 ? '+' : '').number_format($summary['pembulatan'], 0, ',', '.')."\n";
                $text .= 'Uang Hari Ini Rp. '.number_format($summary['payout'], 0, ',', '.')."\n";
            }

            // === FOOTER ID ===
            $footerProduk = self::abbreviate($sections[0]['produk']->nama);
            $text .= "{$no_nota} | ".strtoupper($produsen->nama)." | {$footerProduk} | {$tanggalShort} |".
                (($produsen->bundle_ke ?? 0) > 0 ? ' B'.$produsen->bundle_ke : '')."\n";
            $text .= "* PERIKSA UANG SEBELUM PERGI. HUBUNGI ADMIN JIKA ADA SELISIH.\n";
        }

        // === SAVE LOGIC ===
        $folderPath = 'nota_logs/'.date('Y-m-d', strtotime($date));

        if (! Storage::disk('local')->exists($folderPath)) {
            Storage::disk('local')->makeDirectory($folderPath);
        }

        $files = Storage::disk('local')->files($folderPath);
        $shouldBackup = true;

        if (! empty($files)) {
            rsort($files);
            $latestContent = Storage::disk('local')->get($files[0]);

            if ($latestContent === $text) {
                $shouldBackup = false;
            }
        }

        if ($shouldBackup) {
            $filename = "{$folderPath}/nota_".time().'.txt';
            Storage::disk('local')->put($filename, $text);
        }
    }

    /**
     * Calculate financial summary matching content.blade.php logic exactly.
     */
    private static function calculateSummary(array $nota): array
    {
        $t = $nota['transaksi'];
        $isOk = $t && strtolower($t->status) === 'ok';

        $snapshot = null;
        if ($isOk && ! empty($t->keterangan)) {
            $decoded = json_decode((string) $t->keterangan, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['v'])) {
                $snapshot = $decoded;
            }
        }

        if ($snapshot) {
            return [
                'bayar' => (float) $snapshot['bruto'],
                'kas' => (float) $t->kas,
                'kemarin' => (float) $t->kemarin,
                'lain' => (float) $snapshot['lain'],
                'tabungan' => (float) $snapshot['tabungan'],
                'pembulatan' => (float) $t->pembulatan,
                'payout' => (float) $t->jumlah,
            ];
        } elseif ($isOk) {
            $lain = (float) (DetailTransaksi::where('transaksi_id', $t->id)->sum('jumlah') ?? 0);
            $tabunganRecord = DetailTabungan::where('transaksi_id', $t->id)->first();
            $tabungan = $tabunganRecord ? abs((float) $tabunganRecord->jumlah) : 0;

            return [
                'bayar' => $nota['totalBayarProdusen'] ?? 0,
                'kas' => (float) $t->kas,
                'kemarin' => (float) $t->kemarin,
                'lain' => $lain,
                'tabungan' => $tabungan,
                'pembulatan' => (float) $t->pembulatan,
                'payout' => (float) $t->jumlah,
            ];
        } else {
            $sim = $nota['sim_result'] ?? null;
            if (!$sim) {
                $settlement = app(SettlementService::class);
                $produsen = $nota['produsen'];
                $sim = $settlement->previewProdusenSettlement(
                    (float) ($nota['totalBayarProdusen'] ?? 0),
                    0,
                    (float) ($produsen->tabungan_rate ?? 0),
                    $t ? $t->id : 0,
                    null
                );
            }

            return [
                'bayar' => (float) ($nota['totalBayarProdusen'] ?? 0),
                'kas' => $sim['kas'],
                'kemarin' => $sim['kemarin'],
                'lain' => $sim['lain'],
                'tabungan' => $sim['tabungan'],
                'pembulatan' => $sim['pembulatan_adjustment'] ?? 0,
                'payout' => $sim['payout'],
            ];
        }
    }

    /**
     * Simple product name abbreviation (mirror of PHP helper).
     */
    private static function abbreviate(string $name): string
    {
        if (function_exists('abbreviateProductName')) {
            return abbreviateProductName($name);
        }

        return $name;
    }
}
