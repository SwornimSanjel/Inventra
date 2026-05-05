<?php

require_once __DIR__ . '/../config/database.php';

class GeminiInsightService
{
    private const DEFAULT_MODEL = 'gemini-2.5-flash';
    private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function generateInsight(array $forecastResult): array
    {
        $fallbackInsight = $this->buildFallbackInsight($forecastResult);
        $apiKey = Database::getEnv('GEMINI_API_KEY');
        $model = Database::getEnv('GEMINI_MODEL', self::DEFAULT_MODEL) ?? self::DEFAULT_MODEL;

        if ($apiKey === null || trim($apiKey) === '') {
            return [
                'source' => 'fallback',
                'insight' => $fallbackInsight,
            ];
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'You explain existing inventory forecasts without changing any figures.',
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $this->buildPrompt($forecastResult),
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topP' => 0.1,
                'topK' => 1,
                'maxOutputTokens' => 120,
            ],
        ];

        $response = $this->sendRequest($model, $apiKey, $payload);
        if (!$response['ok']) {
            return [
                'source' => 'fallback',
                'insight' => $fallbackInsight,
            ];
        }

        $insight = $this->extractInsightFromResponse($response['body'], $forecastResult);
        if ($insight === null) {
            return [
                'source' => 'fallback',
                'insight' => $fallbackInsight,
            ];
        }

        return [
            'source' => 'gemini',
            'insight' => $insight,
        ];
    }

    private function sendRequest(string $model, string $apiKey, array $payload): array
    {
        $url = sprintf(self::API_ENDPOINT, rawurlencode($model));
        $ch = curl_init($url);

        if ($ch === false) {
            return ['ok' => false, 'body' => null];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 20,
        ]);

        $rawBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawBody === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
            return ['ok' => false, 'body' => null];
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'body' => null];
        }

        return ['ok' => true, 'body' => $decoded];
    }

    private function buildPrompt(array $forecastResult): string
    {
        return implode("\n", [
            'You are an inventory forecasting assistant for Inventra.',
            'Explain the following predictive forecast result in simple admin-friendly language.',
            'Do not change any numbers.',
            'Do not invent data.',
            'Do not recommend a different quantity.',
            'Keep the response under 80 words.',
            'Write one explanation sentence and one action sentence.',
            'Use exactly two complete sentences.',
            'Mention only the provided values.',
            'Format the action sentence around the existing suggested reorder quantity and status.',
            '',
            'Product: ' . $forecastResult['product_name'],
            'Current stock: ' . $forecastResult['current_stock'] . ' units',
            'Average daily usage: ' . $forecastResult['daily_usage'] . ' units/day',
            'Predicted runout: ' . $forecastResult['runout_days'] . ' days',
            'Demand trend: ' . $forecastResult['trend'],
            'Suggested reorder quantity: ' . $forecastResult['suggested_reorder'] . ' units',
            'Status: ' . $forecastResult['status'],
            'Threshold: ' . $forecastResult['threshold'],
        ]);
    }

    private function extractInsightFromResponse(array $responseBody, array $forecastResult): ?string
    {
        $parts = $responseBody['candidates'][0]['content']['parts'] ?? null;
        if (!is_array($parts)) {
            return null;
        }

        $segments = [];
        foreach ($parts as $part) {
            if (!is_array($part) || !isset($part['text'])) {
                continue;
            }

            $segments[] = trim((string) $part['text']);
        }

        $text = trim(preg_replace('/\s+/', ' ', implode(' ', $segments)) ?? '');
        if ($text === '') {
            return null;
        }

        $text = trim($text, " \t\n\r\0\x0B\"");
        if ($text === '') {
            return null;
        }

        if ($this->containsUnexpectedNumbers($text, $forecastResult)) {
            return null;
        }

        $words = preg_split('/\s+/', $text) ?: [];
        if (count($words) > 80) {
            $text = implode(' ', array_slice($words, 0, 80));
            $text = rtrim($text, " ,;");
            if (!preg_match('/[.!?]$/', $text)) {
                $text .= '.';
            }
        }

        if (!$this->isUsableInsight($text)) {
            return null;
        }

        return $text;
    }

    private function isUsableInsight(string $text): bool
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        if (count($words) < 8 || count($words) > 80) {
            return false;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', trim($text)) ?: [];
        $sentences = array_values(array_filter($sentences, static fn(string $sentence): bool => trim($sentence) !== ''));

        return count($sentences) >= 2;
    }

    private function containsUnexpectedNumbers(string $text, array $forecastResult): bool
    {
        preg_match_all('/\d+(?:\.\d+)?/', $text, $matches);
        $numbersInResponse = $matches[0] ?? [];

        if ($numbersInResponse === []) {
            return false;
        }

        $allowedNumbers = [];
        foreach ([
            $forecastResult['current_stock'],
            $forecastResult['daily_usage'],
            $forecastResult['runout_days'],
            $forecastResult['suggested_reorder'],
        ] as $value) {
            preg_match_all('/\d+(?:\.\d+)?/', (string) $value, $valueMatches);
            foreach ($valueMatches[0] ?? [] as $number) {
                $allowedNumbers[$this->normalizeNumber($number)] = true;
            }
        }

        if (preg_match_all('/\d+(?:\.\d+)?/', (string) $forecastResult['threshold'], $thresholdMatches)) {
            foreach ($thresholdMatches[0] ?? [] as $number) {
                $allowedNumbers[$this->normalizeNumber($number)] = true;
            }
        }

        foreach ($numbersInResponse as $number) {
            if (!isset($allowedNumbers[$this->normalizeNumber($number)])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeNumber(string $number): string
    {
        if (!str_contains($number, '.')) {
            return ltrim($number, '0') === '' ? '0' : ltrim($number, '0');
        }

        $trimmed = rtrim(rtrim($number, '0'), '.');
        if ($trimmed === '' || $trimmed === '-0') {
            return '0';
        }

        return $trimmed;
    }

    private function buildFallbackInsight(array $forecastResult): string
    {
        return sprintf(
            '%s has %s units in stock, is averaging %s units/day, and may run out in %s days with %s status. Follow the predictive suggestion to reorder %s units and review it against the threshold %s.',
            $forecastResult['product_name'],
            $forecastResult['current_stock'],
            $forecastResult['daily_usage'],
            $forecastResult['runout_days'],
            $forecastResult['status'],
            $forecastResult['suggested_reorder'],
            $forecastResult['threshold']
        );
    }
}
