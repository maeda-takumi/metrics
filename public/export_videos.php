<?php
require_once __DIR__ . '/../models/video.php';

/**
 * Validate and normalize performer_id from the query string.
 */
function getPerformerId(): ?int {
    if (!isset($_GET['performer_id']) || $_GET['performer_id'] === '') {
        return null;
    }

    $performerId = filter_var($_GET['performer_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    if ($performerId === false) {
        http_response_code(400);
        exit('Invalid performer_id');
    }

    return $performerId;
}

/**
 * Convert values to a CSV-safe encoding (SJIS-win) to support multibyte output.
 */
function toCsvEncoding(array $row): array {
    return array_map(static function ($value) {
        $stringValue = (string) ($value ?? '');
        return mb_convert_encoding($stringValue, 'SJIS-win', 'UTF-8');
    }, $row);
}

/**
 * Fetch column names even when there are no rows.
 */
function extractColumns(PDOStatement $stmt): array {
    $columns = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $meta = $stmt->getColumnMeta($i);
        if ($meta && isset($meta['name'])) {
            $columns[] = $meta['name'];
        }
    }
    return $columns;
}

$performerId = getPerformerId();

try {
    $videoModel = new Video();
    $stmt = $videoModel->exportStatement($performerId);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    exit('Failed to export CSV.');
}

$columns = !empty($rows) ? array_keys($rows[0]) : extractColumns($stmt);

$fileLabel = $performerId !== null ? "videos_performer_{$performerId}" : 'videos_all';
$filename = $fileLabel . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=Shift_JIS');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if (!empty($columns)) {
    fputcsv($output, toCsvEncoding($columns));
}

foreach ($rows as $row) {
    fputcsv($output, toCsvEncoding($row));
}

fclose($output);
exit;