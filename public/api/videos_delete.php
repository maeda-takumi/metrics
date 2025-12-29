<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../models/video.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload) && !empty($_POST)) {
    $payload = $_POST;
}

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエスト形式です。']);
    exit;
}

$performerId = isset($payload['performer_id']) ? (int) $payload['performer_id'] : null;
$primaryId = isset($payload['id']) ? (int) $payload['id'] : null;
$videoId = isset($payload['video_id']) ? trim((string) $payload['video_id']) : null;

if ($performerId === null || $performerId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'performer_id は必須です。']);
    exit;
}

if (($primaryId === null || $primaryId < 1) && ($videoId === null || $videoId === '')) {
    http_response_code(400);
    echo json_encode(['error' => '削除対象が指定されていません。']);
    exit;
}

$videoModel = new Video();

try {
    $target = $videoModel->findForDeletion(
        $primaryId !== null && $primaryId > 0 ? $primaryId : null,
        $videoId,
        $performerId
    );

    if ($target === null) {
        http_response_code(404);
        echo json_encode(['error' => '指定された動画が見つかりません。']);
        exit;
    }

    $deleted = $videoModel->deleteById(
        $target['id'] ?? $primaryId,
        $target['video_id'] ?? $videoId,
        $performerId
    );
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => '動画の削除に失敗しました。', 'message' => $e->getMessage()]);
    exit;
}

if (!$deleted) {
    http_response_code(500);
    echo json_encode(['error' => '動画の削除に失敗しました。']);
    exit;
}

echo json_encode([
    'message' => '動画を削除しました。',
    'deleted_id' => $target['id'] ?? $primaryId,
    'deleted_video_id' => $target['video_id'] ?? $videoId,
]);
