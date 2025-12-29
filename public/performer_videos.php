<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/performer.php';
require_once __DIR__ . '/../models/video.php';

function getPerformerId(): ?int {
    $performerId = filter_input(INPUT_GET, 'performer_id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    return $performerId === false ? null : $performerId;
}

$performerId = getPerformerId();
$errorMessage = null;
$performer = null;
$videoList = [];

if ($performerId === null) {
    http_response_code(400);
    $errorMessage = 'performer_id が正しく指定されていません。';
} else {
    $performerModel = new Performer();
    $videoModel = new Video();

    $matches = $performerModel->where('performer_id', $performerId);
    $performer = $matches[0] ?? null;

    if ($performer === null) {
        http_response_code(404);
        $errorMessage = '指定されたパフォーマーは見つかりませんでした。';
    } else {
        $videoList = $videoModel->findByPerformerId($performerId);
    }
}

View::render('content/performer_videos.php', [
    'title' => 'パフォーマー動画一覧',
    'performer' => $performer,
    'performerId' => $performerId,
    'videoList' => $videoList,
    'errorMessage' => $errorMessage,
]);