<?php
/**
 * Minimal file logger: backend/logs/info.log and backend/logs/error.log.
 * Milestone 3 uses it for external API calls and server errors;
 * Milestone 4 adds request logging on top of it.
 */
class Logger
{
    public static function info(string $message): void
    {
        self::write('info', 'INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('error', 'ERROR', $message);
    }

    private static function write(string $file, string $level, string $message): void
    {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir) || !is_writable($dir)) {
            return;   // logging must never break a request
        }
        $line = sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $level, str_replace(["\r", "\n"], ' ', $message));
        @file_put_contents("$dir/$file.log", $line, FILE_APPEND | LOCK_EX);
    }
}
