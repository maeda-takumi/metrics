<?php
require_once '../core/View.php';
require_once '../models/performer.php';

$performerModel = new Performer();
$performers = $performerModel->all();

// YouTube の動画 ID を抽出し、サムネイル情報を組み立てる
$extractVideoId = static function (string $value): ?string {
    // URL 形式にも対応
    if (preg_match('/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/', $value, $matches)) {
        return $matches[1];
    }

    // 11 文字の動画 ID のみが渡された場合
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }

    return null;
};

$videos = array_values(array_filter(array_map(static function ($performer) use ($extractVideoId) {
    if (empty($performer['video_tag'])) {
        return null;
    }

    $videoId = $extractVideoId((string)$performer['video_tag']);

    if ($videoId === null) {
        return null;
    }

    $title = $performer['video_title']
        ?? $performer['video_name']
        ?? $performer['performer_name']
        ?? 'YouTube動画';

    return [
        'video_id' => $videoId,
        'title' => $title,
        'performer_name' => $performer['performer_name'] ?? '',
        'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
    ];
}, $performers)));

View::render('content/home.php', [
    'title' => 'パフォーマー一覧',
    'performers' => $performers,
    'videos' => $videos
]);