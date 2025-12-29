<?php
$videoList = $videoList ?? [];
$performerId = $performerId ?? '';
$videoSectionClass = $videoSectionClass ?? 'video-section';

// URL/ID から YouTube の動画 ID を抽出
$extractVideoId = static function ($value): ?string {
    if (!is_string($value) || $value === '') {
        return null;
    }
    if (preg_match('/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/', $value, $m)) {
        return $m[1];
    }
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }
    return null;
};

// 再生数の表示整形
$formatMetric = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format((int) $value);
};
?>

<?php if (!empty($videoList)): ?>
<section class="card <?= htmlspecialchars($videoSectionClass, ENT_QUOTES, 'UTF-8') ?>">
    <header class="video-header">
        <h3>動画一覧<?= $performerId ? "（performer_id: " . htmlspecialchars((string) $performerId, ENT_QUOTES, 'UTF-8') . "）" : '' ?></h3>
        <p class="muted">サムネイルをクリックすると YouTube で再生します</p>
    </header>

    <ul class="video-list">
        <?php foreach ($videoList as $video): ?>
            <?php
                $videoId = $extractVideoId($video['video_id'] ?? $video['video_tag'] ?? '');
                $thumbUrl = $videoId ? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg" : '';
                $videoUrl = $videoId ? "https://www.youtube.com/watch?v={$videoId}" : '';
            ?>
            <li class="video-item">
                <div class="video-content">
                    <div class="video-title-row">
                        <h4><?= htmlspecialchars($video['video_tag'] ?? '動画タグなし', ENT_QUOTES, 'UTF-8') ?></h4>
                        <?php if (!empty($video['video_id'])): ?>
                            <span class="badge-subtle">video_id: <?= htmlspecialchars($video['video_id'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($videoUrl): ?>
                        <p class="video-link">
                            <a href="<?= htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="video-link muted">YouTube の動画 ID が不明です</p>
                    <?php endif; ?>

                    <dl class="video-metrics">
                        <div><dt>view_7h</dt><dd><?= $formatMetric($video['view_7h'] ?? null) ?></dd></div>
                        <div><dt>view_12h</dt><dd><?= $formatMetric($video['view_12h'] ?? null) ?></dd></div>
                        <div><dt>view_24h</dt><dd><?= $formatMetric($video['view_24h'] ?? null) ?></dd></div>
                        <div><dt>view_48h</dt><dd><?= $formatMetric($video['view_48h'] ?? null) ?></dd></div>
                        <div><dt>view_month</dt><dd><?= $formatMetric($video['view_month'] ?? null) ?></dd></div>
                        <div><dt>view_real</dt><dd><?= $formatMetric($video['view_real'] ?? null) ?></dd></div>
                    </dl>
                </div>

                <div class="view-action">
                    <?php if ($thumbUrl): ?>
                        <a class="video-thumb" href="<?= htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($video['video_tag'] ?? 'YouTube サムネイル', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        </a>
                    <?php else: ?>
                        <div class="placeholder-avatar">No Thumbnail</div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php else: ?>
    <div class="card empty-state">
        <h2>動画が登録されていません</h2>
        <p>このパフォーマーに紐づく動画はありません。</p>
    </div>
<?php endif; ?>
