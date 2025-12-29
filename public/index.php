<?php
require_once '../core/View.php';
require_once '../models/performer.php';
require_once '../models/video.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fetch_view_count') {
    $videoId = trim($_POST['video_id'] ?? '');
    $performerId = trim($_POST['performer_id'] ?? '');

    header('Content-Type: application/json; charset=utf-8');

    if ($videoId === '' || $performerId === '') {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'message' => '動画IDまたはパフォーマーIDが指定されていません。',
        ]);
        exit;
    }

    // 将来的にここで実際のビュー数取得処理を呼び出せるようにしている
    echo json_encode([
        'status' => 'queued',
        'message' => 'ビュー数の取得リクエストを受け付けました。',
        'video_id' => htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'),
        'performer_id' => htmlspecialchars($performerId, ENT_QUOTES, 'UTF-8'),
    ]);
    exit;
}

$performerModel = new Performer();
$videoModel = new Video();

$performers = $performerModel->all();
$videosByPerformer = [];

if (!empty($performers)) {
    $performerIds = array_filter(array_map(fn($performer) => $performer['performer_id'] ?? $performer['id'] ?? null, $performers));
    $videos = $videoModel->findByPerformerIds($performerIds);

    foreach ($videos as $video) {
        $performerId = $video['performer_id'] ?? null;
        if ($performerId !== null) {
            $videosByPerformer[$performerId][] = $video;
        }
    }
}

View::render('content/home.php', [
    'title' => 'パフォーマー一覧',
    'performers' => $performers,
    'videosByPerformer' => $videosByPerformer,
]);