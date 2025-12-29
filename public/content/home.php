<div class="page-header">
    <div>
        <p class="eyebrow">Dashboard</p>
        <h1>パフォーマー一覧</h1>
        <p class="lede">YouTube 指標の取得対象となるパフォーマーをカードで確認できます。</p>
    </div>
    <form class="csv-export-form" action="export_videos.php" method="get">
        <label for="csv-performer" class="csv-export-label">CSV出力</label>
        <div class="csv-export-controls">
            <select id="csv-performer" name="performer_id">
                <option value="">すべてのパフォーマー</option>
                <?php foreach ($performers as $performer): ?>
                    <option value="<?= htmlspecialchars($performer['performer_id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($performer['performer_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button primary">CSV出力</button>
        </div>
    </form>    
</div>
<?php if (!empty($videos)): ?>
    <section class="card video-section">
        <header class="video-section__header">
            <div>
                <p class="eyebrow">Videos</p>
                <h2 class="video-section__title">動画一覧</h2>
            </div>
            <p class="video-section__hint">サムネイルをクリックすると YouTube で再生できます。</p>
        </header>
        <div class="video-grid" role="list">
            <?php foreach ($videos as $video): ?>
                <article class="video-card" role="listitem">
                    <a class="video-thumb" href="https://www.youtube.com/watch?v=<?= htmlspecialchars($video['video_id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                        <img
                            src="<?= htmlspecialchars($video['thumbnail_url'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($video['title'] ?? 'YouTube動画', ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                        >
                    </a>
                    <div class="video-meta">
                        <h3 class="video-title">
                            <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($video['video_id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($video['title'] ?? 'YouTube動画', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h3>
                        <?php if (!empty($video['performer_name'])): ?>
                            <p class="video-performer">出演: <?= htmlspecialchars($video['performer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<div class="performer-grid">
    <?php if (!empty($performers)): ?>
        <?php foreach ($performers as $performer): ?>
            <?php
                $performerId = $performer['performer_id'] ?? $performer['id'] ?? '';
                $videoList = $videosByPerformer[$performerId] ?? [];
            ?>
            <article class="performer-card">
                <div class="performer-thumb">
                    <?php if (!empty($performer['img'])): ?>
                        <img src="../img/<?= htmlspecialchars($performer['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($performer['performer_name'] ?? 'Performer', ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="placeholder-avatar">No Image</div>
                    <?php endif; ?>
                </div>
                <div class="performer-body">
                    <div class="performer-title">
                        <h2><?= htmlspecialchars($performer['performer_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($performer['performer'])): ?>
                            <span class="badge"><?= htmlspecialchars($performer['performer'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="meta">
                        performer_id: <span><?= htmlspecialchars($performer['performer_id'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <?php if (!empty($performer['video_tag'])): ?>
                        <p class="meta">タグ: <span><?= htmlspecialchars($performer['video_tag'], ENT_QUOTES, 'UTF-8') ?></span></p>
                    <?php endif; ?>
                    <?php if ($performerId !== ''): ?>
                        <div class="performer-actions">
                            <a class="button primary" href="performer_videos.php?performer_id=<?= htmlspecialchars((string)$performerId, ENT_QUOTES, 'UTF-8') ?>">動画一覧</a>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php include __DIR__ . '/partials/video-list.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card empty-state">
            <h2>パフォーマーが登録されていません</h2>
            <p>performer テーブルにレコードを追加すると、ここに表示されます。</p>
        </div>
    <?php endif; ?>
</div>
