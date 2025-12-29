<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../models/video.php';
require_once __DIR__ . '/../../services/YouTubeClient.php';

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

$performerId = isset($payload['performer_id']) ? (int) $payload['performer_id'] : null;
$videoIds = $payload['video_ids'] ?? [];

if ($performerId === null || empty($videoIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'performer_id and video_ids are required.']);
    exit;
}

if (!is_array($videoIds)) {
    if (is_string($videoIds)) {
        $videoIds = array_map('trim', explode(',', $videoIds));
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'video_ids must be an array or comma-separated string.']);
        exit;
    }
}

try {
    $client = new YouTubeClient();
    $result = $client->fetchVideoStatistics($videoIds);
} catch (YouTubeApiException $e) {
    $status = $e->isQuotaError() ? 429 : 502;
    http_response_code($status);
    echo json_encode([
        'error' => 'YouTube API error',
        'message' => $e->getMessage(),
        'reason' => $e->getReason(),
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$videoModel = new Video();
$updated = [];
$updateErrors = [];

$now = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s');

foreach ($result['statistics'] as $videoId => $stats) {
    $viewCount = isset($stats['viewCount']) ? (int) $stats['viewCount'] : null;
    if ($viewCount === null) {
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'viewCount not returned.'
        ];
        continue;
    }

    $success = $videoModel->updateByVideoId($videoId, [
        'view_count' => $viewCount,
        'last_fetched_at' => $now
    ], $performerId);

    if ($success) {
        $updated[] = [
            'video_id' => $videoId,
            'view_count' => $viewCount,
            'last_fetched_at' => $now
        ];
    } else {
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'Failed to update the video record.'
        ];
    }
}

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $error) {
        $updateErrors[] = $error;
    }
}

echo json_encode([
    'updated' => $updated,
    'errors' => $updateErrors
]);