<?php
require_once __DIR__ . '/../core/Model.php';

class Video extends Model {
    protected $table = 'video';

    /**
     * @param array $performerIds
     * @return array
     */
    public function findByPerformerIds(array $performerIds): array
    {
        if (empty($performerIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($performerIds), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE performer_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($performerIds));

        return $stmt->fetchAll();
    }
}