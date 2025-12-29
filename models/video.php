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

    public function findByPrimaryKey(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);

        $result = $stmt->fetch();

        return $result === false ? null : $result;
    }

    public function create(array $data): array {
        $payload = [
            'performer_id' => isset($data['performer_id']) ? (int) $data['performer_id'] : null,
            'video_tag' => isset($data['video_tag']) ? trim((string) $data['video_tag']) : '',
            'video_id' => isset($data['video_id']) ? trim((string) $data['video_id']) : '',
        ];

        if ($payload['performer_id'] === null || $payload['performer_id'] < 1) {
            throw new InvalidArgumentException('performer_id is required.');
        }

        if ($payload['video_tag'] === '') {
            throw new InvalidArgumentException('video_tag is required.');
        }

        if ($payload['video_id'] === '') {
            throw new InvalidArgumentException('video_id is required.');
        }

        $success = $this->insert($payload);

        if (!$success) {
            throw new RuntimeException('Failed to insert video.');
        }

        $insertId = (int) $this->pdo->lastInsertId();

        if ($insertId > 0) {
            $record = $this->findByPrimaryKey($insertId);
            if ($record !== null) {
                return $record;
            }
        }

        $fallback = $this->findByVideoAndPerformer($payload['video_id'], $payload['performer_id']);

        return $fallback ?? $payload;
    }

    public function findByVideoAndPerformer(string $videoId, int $performerId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE video_id = ? AND performer_id = ? LIMIT 1");
        $stmt->execute([$videoId, $performerId]);

        $result = $stmt->fetch();

        return $result === false ? null : $result;
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
    public function findForDeletion(?int $id, ?string $videoId, int $performerId): ?array {
        $matchClauses = [];
        $params = [$performerId];

        if ($id !== null) {
            $matchClauses[] = 'id = ?';
            $params[] = $id;
        }

        if ($videoId !== null && $videoId !== '') {
            $matchClauses[] = 'video_id = ?';
            $params[] = $videoId;
        }

        if (empty($matchClauses)) {
            throw new InvalidArgumentException('Missing delete target');
        }

        $where = 'performer_id = ? AND (' . implode(' OR ', $matchClauses) . ')';
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$where} LIMIT 1");
        $stmt->execute($params);

        $result = $stmt->fetch();

        return $result === false ? null : $result;
    }

    public function deleteById(?int $id, ?string $videoId, int $performerId): bool {
        $matchClauses = [];
        $params = [$performerId];

        if ($id !== null) {
            $matchClauses[] = 'id = ?';
            $params[] = $id;
        }

        if ($videoId !== null && $videoId !== '') {
            $matchClauses[] = 'video_id = ?';
            $params[] = $videoId;
        }

        if ($performerId < 1 || empty($matchClauses)) {
            throw new InvalidArgumentException('A performer_id and delete target are required.');
        }

        $where = 'performer_id = ? AND (' . implode(' OR ', $matchClauses) . ')';

        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$where} LIMIT 1");
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }
}
