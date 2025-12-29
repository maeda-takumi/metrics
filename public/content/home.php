<div class="page-header">
    <div>
        <p class="eyebrow">Dashboard</p>
        <h1>パフォーマー一覧</h1>
        <p class="lede">YouTube 指標の取得対象となるパフォーマーをカードで確認できます。</p>
    </div>
</div>

<div class="performer-grid">
    <?php if (!empty($performers)): ?>
        <?php foreach ($performers as $performer): ?>
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
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card empty-state">
            <h2>パフォーマーが登録されていません</h2>
            <p>performer テーブルにレコードを追加すると、ここに表示されます。</p>
        </div>
    <?php endif; ?>
</div>
public/index.php
