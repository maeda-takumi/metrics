<?php
$videoList = $videoList ?? [];
$performerId = $performerId ?? '';
?>

<div class="video-section">
    <div class="video-header">
        <h3>動画一覧</h3>
        <p class="meta">パフォーマーに紐づく動画のビュー数取得を実行できます。</p>
    </div>

    <?php if (!empty($videoList)): ?>
        <ul class="video-list">
            <?php foreach ($videoList as $video): ?>
                <?php
                $videoTitle = htmlspecialchars($video['title'] ?? 'タイトル不明', ENT_QUOTES, 'UTF-8');
                $videoIdentifier = htmlspecialchars($video['video_id'] ?? $video['id'] ?? '', ENT_QUOTES, 'UTF-8');
                $videoTag = htmlspecialchars($video['tag'] ?? $video['video_tag'] ?? '', ENT_QUOTES, 'UTF-8');
                $videoUrl = htmlspecialchars($video['url'] ?? $video['video_url'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                <li class="video-item">
                    <div class="video-content">
                        <div class="video-title-row">
                            <h4><?= $videoTitle ?></h4>
                            <?php if (!empty($videoTag)): ?>
                                <span class="badge badge-subtle"><?= $videoTag ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="meta">
                            動画ID: <span><?= $videoIdentifier !== '' ? $videoIdentifier : '未設定' ?></span>
                        </p>
                        <?php if (!empty($videoUrl)): ?>
                            <p class="meta video-link">
                                <a href="<?= $videoUrl ?>" target="_blank" rel="noopener noreferrer">動画URLを開く</a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="view-action">
                        <form class="view-count-form" method="post" action="">
                            <input type="hidden" name="action" value="fetch_view_count">
                            <input type="hidden" name="video_id" value="<?= $videoIdentifier ?>">
                            <input type="hidden" name="performer_id" value="<?= htmlspecialchars($performerId, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="button button-primary">実行</button>
                        </form>
                        <p class="view-status" aria-live="polite"></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="meta muted">紐づく動画が登録されていません。</p>
    <?php endif; ?>
</div>
