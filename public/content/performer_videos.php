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
            <div class="performer-actions">
                <button
                    class="button"
                    type="button"
                    data-action="open-video-modal"
                    data-target="#video-add-modal"
                >
                    新規追加
                </button>
                <button
                    class="button secondary"
                    type="button"
                    data-action="fetch-performer-views"
                    data-performer-id="<?= htmlspecialchars((string)$performerId, ENT_QUOTES, 'UTF-8') ?>"
                >
                    再生数を取得
                </button>
                <p class="view-status" data-performer-view-status aria-live="polite"></p>
            </div>
        </div>
    </section>
    <div class="modal" id="video-add-modal" aria-hidden="true" data-modal>
        <div class="modal__backdrop" data-modal-close></div>
        <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="video-add-modal-title">
            <div class="modal__header">
                <h3 id="video-add-modal-title">動画を新規追加</h3>
                <button class="modal__close" type="button" aria-label="閉じる" data-modal-close>&times;</button>
            </div>
            <form class="modal__body" data-video-create-form data-performer-id="<?= htmlspecialchars((string)$performerId, ENT_QUOTES, 'UTF-8') ?>">
                <label for="video_tag">動画タグ</label>
                <input type="text" name="video_tag" id="video_tag" placeholder="例: MV-001" required>

                <label for="video_id">video_id（YouTube の ID または URL）</label>
                <input type="text" name="video_id" id="video_id" placeholder="例: dQw4w9WgXcQ または https://youtu.be/dQw4w9WgXcQ" required>

                <p class="muted">保存後に一覧へ反映されます。</p>
                <button class="button primary" type="submit">保存</button>
                <p class="view-status" data-video-form-status aria-live="polite"></p>
            </form>
        </div>
    </div>
    <?php
    $videoSectionClass = 'video-section video-section--standalone';
    include __DIR__ . '/partials/video-list.php';
    ?>
<?php endif; ?>