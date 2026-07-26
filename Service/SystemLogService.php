<?php

namespace Service;

require_once dirname(__DIR__) . '/App/Logging/SystemLogger.php';

use App\Logging\SystemLogger;
use SplFileObject;

final class SystemLogService
{
    public function getRecordsBetween(
        string $startDate,
        string $endDate,
        ?int $userId = null,
        ?int $firmId = null,
        int $limit = 5000
    ): array {
        $limit = max(1, min($limit, 10000));
        $records = [];
        $files = glob(SystemLogger::logDirectory() . '/system-errors-*.log') ?: [];
        rsort($files, SORT_STRING);

        foreach ($files as $file) {
            if (!preg_match('/system-errors-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                continue;
            }

            $fileDate = $matches[1];
            if ($fileDate < $startDate || $fileDate > $endDate) {
                continue;
            }

            foreach ($this->iterateFileReverse($file) as $record) {
                $actor = is_array($record['actor'] ?? null) ? $record['actor'] : [];
                if ($userId !== null && (int) ($actor['user_id'] ?? 0) !== $userId) {
                    continue;
                }
                if ($firmId !== null && (int) ($actor['firm_id'] ?? 0) !== $firmId) {
                    continue;
                }

                $records[] = $record;
                if (count($records) >= $limit) {
                    break 2;
                }
            }
        }

        return $records;
    }

    public function getDashboardData(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        $records = $this->readRecent($limit);

        return [
            'records' => $records,
            'today' => $this->countFile($this->fileForDate(date('Y-m-d'))),
        ];
    }

    private function readRecent(int $limit): array
    {
        $files = glob(SystemLogger::logDirectory() . '/system-errors-*.log') ?: [];
        rsort($files, SORT_STRING);
        $records = [];

        foreach ($files as $file) {
            foreach ($this->readFile($file, $limit - count($records)) as $record) {
                $records[] = $record;
                if (count($records) >= $limit) {
                    break 2;
                }
            }
        }

        return $records;
    }

    private function readFile(string $file, int $limit): array
    {
        $lines = [];
        foreach ($this->iterateFileReverse($file) as $record) {
            $lines[] = $record;
            if (count($lines) >= $limit) {
                break;
            }
        }

        return $lines;
    }

    private function iterateFileReverse(string $file): \Generator
    {
        if (!is_readable($file)) {
            return;
        }

        $stream = new SplFileObject($file, 'r');
        $stream->seek(PHP_INT_MAX);

        for ($line = $stream->key(); $line >= 0; $line--) {
            $stream->seek($line);
            $content = trim((string) $stream->current());
            if ($content === '') {
                continue;
            }
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                yield $decoded;
            }
        }
    }

    private function fileForDate(string $date): string
    {
        return SystemLogger::logDirectory() . '/system-errors-' . $date . '.log';
    }

    private function countFile(string $file): array
    {
        $counts = ['total' => 0, 'critical' => 0, 'error' => 0, 'warning' => 0, 'notice' => 0];
        if (!is_readable($file)) {
            return $counts;
        }

        $stream = new SplFileObject($file, 'r');
        foreach ($stream as $content) {
            $decoded = json_decode(trim((string) $content), true);
            if (!is_array($decoded)) {
                continue;
            }
            $level = (string) ($decoded['level'] ?? 'error');
            $counts['total']++;
            if (isset($counts[$level])) {
                $counts[$level]++;
            }
        }

        return $counts;
    }
}
