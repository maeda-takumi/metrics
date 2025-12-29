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
        <?php endforeach; ?>
    <?php endif; ?>
</div>
