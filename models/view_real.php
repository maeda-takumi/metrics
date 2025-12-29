<?php

require_once __DIR__ . '/../core/Model.php';

class ViewReal extends Model {
    protected $table = 'youtube_video_metrics';

    public function __construct() {
        parent::__construct();
        $this->table = $this->determineTableName();
    }
    private function determineTableName(): string {
        $candidates = array_unique([
            $this->table,
            'videos',
            'video',
            'view_real',
        ]);

        return $this->findExistingTable($candidates) ?? $this->table;
    }
    public function upsertViewReal(string $videoId, int $viewReal, ?int $performerId): bool {
        $sql = <<<SQL
            INSERT INTO {$this->table} (video_id, performer_id, view_real)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                performer_id = VALUES(performer_id),
                view_real = VALUES(view_real),
                updated_at = NOW()
        SQL;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $videoId,
            $performerId,
            $viewReal,
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