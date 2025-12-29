<?php
require_once __DIR__ . '/../core/Model.php';

class Video extends Model {
    protected $table = 'videos';

    public function __construct() {
        parent::__construct();
        $this->table = $this->determineTableName();
    }

    private function determineTableName(): string {
        $candidates = ['videos', 'video'];

        foreach ($candidates as $candidate) {
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$candidate]);

            if ($stmt->fetchColumn()) {
                return $candidate;
            }
        }

        return $this->table;
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
}