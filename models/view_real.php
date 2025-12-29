<?php

require_once __DIR__ . '/../core/Model.php';

class ViewReal extends Model {
    protected $table = 'view_real';

    public function __construct() {
        parent::__construct();
    }

    public function upsertViewCount(string $videoId, int $viewCount, ?int $performerId, string $fetchedAt): bool {
        $sql = <<<SQL
            INSERT INTO {$this->table} (video_id, performer_id, view_count, fetched_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                performer_id = VALUES(performer_id),
                view_count = VALUES(view_count),
                fetched_at = VALUES(fetched_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $videoId,
            $performerId,
            $viewCount,
            $fetchedAt,
        ]);
    }

    public function findByVideoIds(array $videoIds): array {
        $cleanIds = array_values(array_filter(array_map('trim', $videoIds), fn($id) => $id !== ''));
        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE video_id IN ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($cleanIds);

        $rows = $stmt->fetchAll();

        $indexed = [];
        foreach ($rows as $row) {
            if (!empty($row['video_id'])) {
                $indexed[$row['video_id']] = $row;
            }
        }

        return $indexed;
    }
}