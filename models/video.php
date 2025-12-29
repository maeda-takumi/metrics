<?php
require_once __DIR__ . '/../core/Model.php';

class Video extends Model {
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
        ]);

        foreach ($candidates as $candidate) {
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$candidate]);

            if ($stmt->fetchColumn()) {
                return $candidate;
            }
        }

        return $this->table;
    }

    public function findByPerformerId(int $performerId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE performer_id = ?");
        $stmt->execute([$performerId]);

        return $stmt->fetchAll();
    }

    public function exportStatement(?int $performerId = null): PDOStatement {
        $sql = "SELECT v.*, p.performer_name, p.performer, p.video_tag, p.img FROM {$this->table} v LEFT JOIN performer p ON v.performer_id = p.performer_id";
        $params = [];

        if ($performerId !== null) {
            $sql .= ' WHERE v.performer_id = ?';
            $params[] = $performerId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function updateByVideoId(string $videoId, array $data, ?int $performerId = null): bool {
        if (empty($data)) {
            return false;
        }

        $setClause = implode(', ', array_map(fn($column) => "$column = ?", array_keys($data)));
        $values = array_values($data);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE video_id = ?";
        $values[] = $videoId;

        if ($performerId !== null) {
            $sql .= " AND performer_id = ?";
            $values[] = $performerId;
        }

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($values);
    }
}