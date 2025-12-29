<?php
/** @var array|null $performer */
/** @var array $videoList */
/** @var int|null $performerId */
/** @var string|null $errorMessage */

$performerName = $performer['performer_name'] ?? 'パフォーマー';
?>

<div class="page-header">
    <div>
        <p class="eyebrow">Videos</p>
        <h1><?= htmlspecialchars($performerName, ENT_QUOTES, 'UTF-8') ?> の動画</h1>
        <p class="lede">パフォーマーに紐づく動画の一覧とビュー数取得の操作ができます。</p>
    </div>
    <a class="button primary" href="index.php">パフォーマー一覧へ戻る</a>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="card empty-state">
        <h2>動画を表示できません</h2>
        <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <p><a href="index.php">パフォーマー一覧に戻る</a></p>
    </div>
<?php else: ?>
    <section class="performer-card">
        <div class="performer-thumb">
            <?php if (!empty($performer['img'])): ?>
                <img src="../img/<?= htmlspecialchars($performer['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($performerName, ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
                <div class="placeholder-avatar">No Image</div>
            <?php endif; ?>
        </div>
        <div class="performer-body">
            <div class="performer-title">
                <h2><?= htmlspecialchars($performerName, ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!empty($performer['performer'])): ?>
                    <span class="badge"><?= htmlspecialchars($performer['performer'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <p class="meta">
                performer_id: <span><?= htmlspecialchars((string)$performer['performer_id'], ENT_QUOTES, 'UTF-8') ?></span>
            </p>
            <?php if (!empty($performer['video_tag'])): ?>
                <p class="meta">タグ: <span><?= htmlspecialchars($performer['video_tag'], ENT_QUOTES, 'UTF-8') ?></span></p>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $videoSectionClass = 'video-section video-section--standalone';
    include __DIR__ . '/partials/video-list.php';
    ?>
<?php endif; ?>