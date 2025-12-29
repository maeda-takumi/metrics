<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../models/video.php';

$performerId = filter_input(INPUT_GET, 'performer_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($performerId === false || $performerId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'performer_id を指定してください。']);
    exit;
}

$videoModel = new Video();

try {
    $videos = $videoModel->findByPerformerId((int) $performerId);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => '動画一覧の取得に失敗しました。', 'message' => $e->getMessage()]);
    exit;
}

echo json_encode([
    'videos' => $videos,
]);
