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
$videoTag = isset($payload['video_tag']) ? trim((string) $payload['video_tag']) : '';
$videoId = isset($payload['video_id']) ? trim((string) $payload['video_id']) : '';

if ($performerId === null || $performerId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'performer_id は必須です。']);
    exit;
}

if ($videoTag === '' || $videoId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'video_tag と video_id を入力してください。']);
    exit;
}

$videoModel = new Video();

try {
    $record = $videoModel->create([
        'performer_id' => $performerId,
        'video_tag' => $videoTag,
        'video_id' => $videoId,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => '動画の追加に失敗しました。', 'message' => $e->getMessage()]);
    exit;
}

http_response_code(201);
echo json_encode([
    'message' => '動画を追加しました。',
    'video' => $record,
]);