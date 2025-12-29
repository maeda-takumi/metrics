<?php
declare(strict_types=1);

/**
 * Lightweight structured logger for YouTube fetch/update flows.
 * Uses error_log with a dedicated file destination and JSON lines for readability.
 * Note: consider external rotation if logs may grow large.
 */
final class YouTubeLogger
{
    private const LOG_DIR = __DIR__ . '/../storage/logs';
    private const LOG_FILE = self::LOG_DIR . '/youtube.log';

    public static function log(array $context): void
    {
        $logPath = self::ensureLogFile();
        if ($logPath === null) {
            return;
        }

        $context['timestamp'] = (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())))->format(DATE_ATOM);
        $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded !== false) {
            error_log($encoded . PHP_EOL, 3, $logPath);
        }
    }

    private static function ensureLogFile(): ?string
    {
        if (!is_dir(self::LOG_DIR)) {
            if (!mkdir(self::LOG_DIR, 0775, true) && !is_dir(self::LOG_DIR)) {
                return null;
            }
        }

        return self::LOG_FILE;
    }
}