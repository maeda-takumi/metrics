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
        $setClauses = [
            'view_real = ?',
            'updated_at = NOW()'
        ];
        $where = 'video_id = ?';

        $params = [$viewReal];
        $whereParams = [$videoId];

        if ($performerId !== null) {
            $setClauses[] = 'performer_id = ?';
            $params[] = $performerId;
            $where .= ' AND performer_id = ?';
            $whereParams[] = $performerId;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->table,
            implode(', ', $setClauses),
            $where
        );

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute(array_merge($params, $whereParams));

        if ($stmt->rowCount() === 0) {
            $existsSql = "SELECT 1 FROM {$this->table} WHERE {$where} LIMIT 1";
            $existsStmt = $this->pdo->prepare($existsSql);
            $existsStmt->execute($whereParams);

            if ($existsStmt->fetchColumn() === false) {
                $performerMessage = $performerId !== null ? " for performer {$performerId}" : '';
                throw new RuntimeException("No {$this->table} row matched video_id {$videoId}{$performerMessage}.");
            }
        }

        return true;
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