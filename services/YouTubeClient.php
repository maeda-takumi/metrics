<?php

class YouTubeApiException extends RuntimeException {
    private ?string $reason;
    private array $response;

    public function __construct(string $message, int $code = 0, ?string $reason = null, array $response = []) {
        parent::__construct($message, $code);
        $this->reason = $reason;
        $this->response = $response;
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function getResponse(): array {
        return $this->response;
    }

    public function isQuotaError(): bool {
        return in_array($this->reason, ['quotaExceeded', 'dailyLimitExceeded', 'rateLimitExceeded'], true);
    }
}

class YouTubeClient {
    private string $apiKey;
    private string $baseUrl;
    private int $maxBatchSize;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, ?int $maxBatchSize = null) {
        $config = require __DIR__ . '/../config/youtube.php';

        $this->apiKey = $apiKey ?? ($config['api_key'] ?? '');
        $this->baseUrl = rtrim($baseUrl ?? ($config['base_url'] ?? ''), '/');
        $this->maxBatchSize = $maxBatchSize ?? ($config['max_batch_size'] ?? 45);

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('YouTube API key is not configured.');
        }
        if (empty($this->baseUrl)) {
            throw new InvalidArgumentException('YouTube API base URL is not configured.');
        }
    }

    /**
     * @param array $videoIds
     * @return array [statistics => ['videoId' => ['viewCount' => ...]], errors => []]
     * @throws YouTubeApiException
     */
    public function fetchVideoStatistics(array $videoIds): array {
        $cleanIds = array_values(array_filter(array_map('trim', $videoIds), fn($id) => $id !== ''));
        if (empty($cleanIds)) {
            throw new InvalidArgumentException('No video IDs provided.');
        }

        $statistics = [];
        $errors = [];

        foreach (array_chunk($cleanIds, $this->maxBatchSize) as $chunk) {
            try {
                $response = $this->request('/videos', [
                    'part' => 'statistics',
                    'id' => implode(',', $chunk)
                ]);

                $items = $response['items'] ?? [];
                $receivedIds = [];

                foreach ($items as $item) {
                    if (empty($item['id']) || empty($item['statistics'])) {
                        continue;
                    }
                    $statistics[$item['id']] = $item['statistics'];
                    $receivedIds[] = $item['id'];
                }

                $missing = array_diff($chunk, $receivedIds);
                foreach ($missing as $missingId) {
                    $errors[] = [
                        'video_id' => $missingId,
                        'message' => 'Statistics not returned for this video ID.'
                    ];
                }
            } catch (YouTubeApiException $e) {
                $errors[] = [
                    'video_ids' => $chunk,
                    'message' => $e->getMessage(),
                    'reason' => $e->getReason()
                ];

                if ($e->isQuotaError()) {
                    break;
                }
            }
        }

        return [
            'statistics' => $statistics,
            'errors' => $errors
        ];
    }

    private function request(string $path, array $params): array {
        $params['key'] = $this->apiKey;
        $url = $this->baseUrl . $path . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error: ' . $error);
        }

        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid response from YouTube API.');
        }

        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown error';
            $reason = $decoded['error']['errors'][0]['reason'] ?? null;
            throw new YouTubeApiException($errorMessage, $httpCode, $reason, $decoded);
        }

        if (isset($decoded['error'])) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown error';
            $reason = $decoded['error']['errors'][0]['reason'] ?? null;
            throw new YouTubeApiException($errorMessage, $httpCode ?: 500, $reason, $decoded);
        }

        return $decoded;
    }
}