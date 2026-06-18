<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class AiController extends Controller
{
    private string $apiKey;

    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    private SalesService $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->salesService = $salesService;
    }

    public function ask(Request $request)
    {
        if (empty($this->apiKey)) {
            return response()->json(['answer' => 'Mohon tambahkan GEMINI_API_KEY di file .env Anda terlebih dahulu.']);
        }

        $date = $request->get('date', now('Asia/Jakarta')->toDateString());
        $question = $request->get('question', '');

        if (empty($question)) {
            return response()->json(['answer' => 'Pertanyaan tidak boleh kosong.']);
        }

        // Get context: Dashboard Hub Data
        $hubData = collect($this->salesService->getDashboardHubData($date));
        $pedagangContext = $hubData->map(function ($item) {
            return [
                'nama' => $item->nama,
                'laku' => $item->laku,
                'titip' => $item->titip,
                'omset' => $item->omset,
                'setoran' => $item->total_setoran,
                'status' => $item->status,
            ];
        })->toArray();

        $prompt = "Anda adalah Asisten AI untuk aplikasi Finansial Citroroso v2. Tugas Anda menjawab pertanyaan admin mengenai laporan pedagang HARI INI ({$date}).\n\n";
        $prompt .= "Berikut adalah data JSON rekap pedagang hari ini:\n";
        $prompt .= json_encode($pedagangContext)."\n\n";
        $prompt .= "Jawab pertanyaan admin secara singkat, padat, dan langsung gunakan data di atas. Jangan buat format markdown yang ribet, gunakan *bold* untuk penekanan dan list `-` jika menyebutkan banyak nama. Jika data tidak ada, bilang tidak ada. \n\n";
        $prompt .= "Pertanyaan Admin: {$question}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'?key='.$this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, gagal memproses respons AI.';
                // Format markdown to basic HTML for UI
                $htmlAnswer = nl2br(htmlspecialchars($answer));
                $htmlAnswer = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $htmlAnswer);

                return response()->json(['answer' => $htmlAnswer]);
            }

            return response()->json(['answer' => 'Error API Gemini: '.$response->body()]);
        } catch (Exception $e) {
            return response()->json(['answer' => 'Terjadi kesalahan sistem: '.$e->getMessage()]);
        }
    }

    public function predictTitip($pedagangId)
    {
        if (empty($this->apiKey)) {
            return response()->json(['error' => 'Mohon tambahkan GEMINI_API_KEY di file .env Anda.'], 400);
        }

        $pedagangName = DB::table('pedagang')->where('id', $pedagangId)->value('nama');

        // Fetch 14 days of history
        $history = DB::table('sales_summaries')
            ->where('type', 'pedagang')
            ->where('type_id', $pedagangId)
            ->where('date', '>=', now('Asia/Jakarta')->subDays(14)->toDateString())
            ->orderBy('date', 'asc')
            ->get(['date', 'total_titip', 'total_laku']);

        if ($history->isEmpty()) {
            return response()->json(['prediction' => 'Data historis belum cukup untuk diprediksi (minimal butuh data 1 hari).']);
        }

        $prompt = "Anda adalah Ahli Logistik AI. Tugas Anda memprediksi alokasi 'Titip' (stok barang) untuk Pedagang bernama '{$pedagangName}' esok hari berdasarkan 14 hari terakhir.\n\n";
        $prompt .= "Data histori (Tgl, Titip, Laku):\n";
        foreach ($history as $h) {
            $persen = $h->total_titip > 0 ? round(($h->total_laku / $h->total_titip) * 100, 1) : 0;
            $prompt .= "- {$h->date}: Titip {$h->total_titip}, Laku {$h->total_laku} ({$persen}%)\n";
        }
        $prompt .= "\nBuatlah 2 kalimat analisis singkat dan sebutkan saran numerik 'Saran Titip Esok' yang spesifik. Harus sangat *to the point*.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'?key='.$this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, gagal memproses respons AI.';
                // Convert asterisks to bold for SweetAlert compatibility
                $htmlAnswer = nl2br(htmlspecialchars($answer));
                $htmlAnswer = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $htmlAnswer);
                $htmlAnswer = preg_replace('/\_(.*?)\_/', '<i>$1</i>', $htmlAnswer);

                return response()->json(['prediction' => $htmlAnswer]);
            }

            return response()->json(['error' => 'API Error ('.$response->status().'): '.$response->body()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'System Error: '.$e->getMessage()], 500);
        }
    }
}
