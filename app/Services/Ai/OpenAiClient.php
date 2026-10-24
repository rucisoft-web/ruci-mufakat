<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis OpenAI Responses API (POST /v1/responses) dengan Structured Outputs.
 * Dipanggil hanya dari backend; kunci API tidak pernah dikirim ke peramban.
 */
class OpenAiClient
{
    public function isConfigured(): bool
    {
        return filled(config('mufakat.ai.api_key'));
    }

    /**
     * @return array{data: array, usage: array, model: string}
     *
     * @throws AiTimeoutException|RuntimeException
     */
    public function structured(string $instructions, string $input, array $schema, string $name = 'mufakat_output'): array
    {
        $payload = [
            'model' => config('mufakat.ai.model'),
            'instructions' => $instructions,
            'input' => [['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $input]]]],
            'text' => ['format' => ['type' => 'json_schema', 'name' => $name, 'schema' => $schema, 'strict' => true]],
            // Data rapat tidak disimpan di sisi penyedia untuk percakapan lanjutan.
            'store' => false,
        ];

        try {
            $response = Http::withToken(config('mufakat.ai.api_key'))
                ->acceptJson()
                ->timeout(config('mufakat.ai.timeout'))
                ->post(rtrim(config('mufakat.ai.base_url'), '/').'/responses', $payload);
        } catch (ConnectionException $e) {
            throw new AiTimeoutException('Layanan AI tidak merespons dalam '.config('mufakat.ai.timeout').' detik.', previous: $e);
        }

        if ($response->status() === 408 || $response->status() === 504) {
            throw new AiTimeoutException('Layanan AI mengalami timeout (HTTP '.$response->status().').');
        }

        if ($response->failed()) {
            throw new RuntimeException('Layanan AI mengembalikan galat '.$response->status().': '.mb_substr((string) $response->json('error.message', $response->body()), 0, 300));
        }

        $text = collect($response->json('output', []))
            ->where('type', 'message')
            ->flatMap(fn ($m) => $m['content'] ?? [])
            ->where('type', 'output_text')
            ->pluck('text')
            ->implode('');

        $refusal = collect($response->json('output', []))->flatMap(fn ($m) => $m['content'] ?? [])->firstWhere('type', 'refusal');

        if ($refusal) {
            throw new RuntimeException('Model menolak permintaan: '.($refusal['refusal'] ?? ''));
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Keluaran AI tidak sesuai format terstruktur.');
        }

        return ['data' => $data, 'usage' => $response->json('usage', []), 'model' => (string) $response->json('model', config('mufakat.ai.model'))];
    }
}
