<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../models/video.php';
require_once __DIR__ . '/../../models/view_real.php';
require_once __DIR__ . '/../../services/YouTubeClient.php';
require_once __DIR__ . '/../../services/YouTubeLogger.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

$performerId = isset($payload['performer_id']) ? (int) $payload['performer_id'] : null;

if ($performerId === null || $performerId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'performer_id is required and must be a positive integer.']);
    exit;
}

// Simple rate limit per performer to avoid excessive API calls
function isRateLimited(int $performerId, int $cooldownSeconds = 30): array {
    $cacheFile = sys_get_temp_dir() . "/performer_views_{$performerId}.json";
    $now = time();

    if (is_file($cacheFile)) {
        $data = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($data) && isset($data['ts']) && ($now - (int) $data['ts']) < $cooldownSeconds) {
            $retryAfter = $cooldownSeconds - ($now - (int) $data['ts']);
            return [true, $retryAfter];
        }
    }

    file_put_contents($cacheFile, json_encode(['ts' => $now]), LOCK_EX);
    return [false, 0];
}

[$limited, $retryAfter] = isRateLimited($performerId);
if ($limited) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate limit exceeded. Please wait before retrying.',
        'retry_after' => $retryAfter,
    ]);
    exit;
}

// Extract YouTube video IDs from stored video data
$extractVideoId = static function ($value): ?string {
    if (!is_string($value) || $value === '') {
        return null;
    }
    if (preg_match('/(?:v=|youtu\\.be\\/|embed\\/)([A-Za-z0-9_-]{11})/', $value, $m)) {
        return $m[1];
    }
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }
    return null;
};

$videoModel = new Video();

try {
    $videos = $videoModel->findByPerformerId($performerId);
} catch (DomainException $e) {
    http_response_code(500);
    echo json_encode(['error' => '動画テーブルが見つかりません。videos テーブルを作成するか、マイグレーションを実行してください。']);
    exit;
}

if (empty($videos)) {
    http_response_code(404);
    echo json_encode(['error' => 'No videos found for the specified performer.']);
    exit;
}

$videoIds = array_values(array_unique(array_filter(array_map(static function ($video) use ($extractVideoId) {
    return $extractVideoId($video['video_id'] ?? $video['video_tag'] ?? '');
} , $videos))));

if (empty($videoIds)) {
    http_response_code(404);
    echo json_encode(['error' => 'No mapped YouTube video IDs for the performer.']);
    exit;
}

try {
    YouTubeLogger::log([
        'event' => 'performer_views.request_received',
        'performer_id' => $performerId,
        'video_ids' => $videoIds,
    ]);
    $client = new YouTubeClient();
    $result = $client->fetchVideoStatistics($videoIds);
} catch (YouTubeApiException $e) {
    $status = $e->isQuotaError() ? 429 : 502;
    YouTubeLogger::log([
        'event' => 'performer_views.youtube_api_exception',
        'performer_id' => $performerId,
        'video_ids' => $videoIds,
        'status' => $status,
        'reason' => $e->getReason(),
        'message' => $e->getMessage(),
        'response_snippet' => $e->getResponse(),
    ]);
    http_response_code($status);
    echo json_encode([
        'error' => 'YouTube API error',
        'message' => $e->getMessage(),
        'reason' => $e->getReason(),
    ]);
    exit;
} catch (Throwable $e) {
    YouTubeLogger::log([
        'event' => 'performer_views.unhandled_exception',
        'performer_id' => $performerId,
        'video_ids' => $videoIds,
        'message' => $e->getMessage(),
    ]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$viewRealModel = new ViewReal();
$updated = [];
$updateErrors = [];

$now = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s');

foreach ($result['statistics'] as $videoId => $stats) {
    $viewCount = isset($stats['viewCount']) ? (int) $stats['viewCount'] : null;

    if ($viewCount === null) {
        YouTubeLogger::log([
            'event' => 'performer_views.view_count_missing',
            'performer_id' => $performerId,
            'video_id' => $videoId,
            'statistics' => $stats,
        ]);
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'viewCount not returned.',
        ];
        continue;
    }

    $successViewReal = false;
    $successVideo = false;
    try {
        $successViewReal = $viewRealModel->upsertViewReal($videoId, $viewCount, $performerId);
    } catch (Throwable $e) {
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'Failed to update view_real: ' . $e->getMessage(),
        ];
        YouTubeLogger::log([
            'event' => 'performer_views.view_real_update_error',
            'performer_id' => $performerId,
            'video_id' => $videoId,
            'view_count' => $viewCount,
            'message' => $e->getMessage(),
        ]);
    }

    try {
        $successVideo = $videoModel->updateByVideoId($videoId, ['view_real' => $viewCount], $performerId);
    } catch (Throwable $e) {
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'Failed to update video table: ' . $e->getMessage(),
        ];
    }

    if ($successViewReal || $successVideo) {
        $updated[] = [
            'video_id' => $videoId,
            'view_count' => $viewCount,
            'fetched_at' => $now,
        ];
    } else {
        $updateErrors[] = [
            'video_id' => $videoId,
            'message' => 'Failed to update database.',
        ];
    }
    YouTubeLogger::log([
        'event' => 'performer_views.update_result',
        'performer_id' => $performerId,
        'video_id' => $videoId,
        'view_count' => $viewCount,
        'success_view_real' => $successViewReal,
        'success_video' => $successVideo,
    ]);
}

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $error) {
        $updateErrors[] = $error;
        YouTubeLogger::log([
            'event' => 'performer_views.api_error',
            'performer_id' => $performerId,
            'video_ids' => $videoIds,
            'error' => $error,
        ]);
    }
}

echo json_encode([
    'updated' => $updated,
    'errors' => $updateErrors,
]);