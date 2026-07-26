<?php

namespace App\Logging;

use ErrorException;
use Throwable;

final class SystemLogger
{
    private static bool $registered = false;
    private static bool $handling = false;
    private static ?string $requestId = null;
    private static ?string $lastErrorSignature = null;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::$requestId = self::createRequestId();

        set_error_handler([self::class, 'handlePhpError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handlePhpError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::$lastErrorSignature = self::errorSignature($severity, $message, $file, $line);
        $isFatal = in_array($severity, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
        self::write($isFatal ? 'critical' : self::severityName($severity), $isFatal ? 'fatal_error' : 'php_error', $message, [
            'file' => $file,
            'line' => $line,
            'php_severity' => $severity,
        ]);

        return false;
    }

    public static function handleException(Throwable $exception): void
    {
        self::exception($exception);

        if (!headers_sent()) {
            http_response_code(500);
            if (self::isJsonRequest()) {
                header('Content-Type: application/json; charset=utf-8');
            }
        }

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, sprintf(
                "Yakalanmamış %s: %s in %s:%d\n",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
            return;
        }

        if (self::isJsonRequest()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Beklenmeyen bir sistem hatası oluştu.',
                'request_id' => self::$requestId,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo 'Beklenmeyen bir sistem hatası oluştu. Hata kodu: '
            . htmlspecialchars((string) self::$requestId, ENT_QUOTES, 'UTF-8');
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }

        if (self::$lastErrorSignature === self::errorSignature(
            (int) $error['type'],
            (string) $error['message'],
            (string) $error['file'],
            (int) $error['line']
        )) {
            return;
        }

        self::write('critical', 'fatal_error', (string) $error['message'], [
            'file' => (string) $error['file'],
            'line' => (int) $error['line'],
            'php_severity' => (int) $error['type'],
        ]);
    }

    public static function exception(Throwable $exception, array $context = []): void
    {
        self::write('error', 'exception', $exception->getMessage(), array_merge($context, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]));
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', 'application_error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', 'application_warning', $message, $context);
    }

    public static function logDirectory(): string
    {
        $configured = getenv('SYSTEM_LOG_PATH');
        return $configured !== false && trim($configured) !== ''
            ? rtrim($configured, DIRECTORY_SEPARATOR)
            : dirname(__DIR__, 2) . '/storage/logs';
    }

    private static function write(string $level, string $type, string $message, array $context): void
    {
        if (self::$handling) {
            return;
        }

        self::$handling = true;

        try {
            $directory = self::logDirectory();
            if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new ErrorException('Sistem log klasörü oluşturulamadı: ' . $directory);
            }

            $record = [
                'timestamp' => date('c'),
                'level' => $level,
                'type' => $type,
                'message' => self::cleanMessage($message),
                'request_id' => self::$requestId ?? self::createRequestId(),
                'request' => self::requestContext(),
                'actor' => self::actorContext(),
                'context' => self::sanitize($context),
            ];

            $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($encoded === false || file_put_contents(
                $directory . '/system-errors-' . date('Y-m-d') . '.log',
                $encoded . PHP_EOL,
                FILE_APPEND | LOCK_EX
            ) === false) {
                throw new ErrorException('Sistem log kaydı yazılamadı.');
            }
        } catch (Throwable $loggingError) {
            error_log('Central logger failure: ' . $loggingError->getMessage() . ' | Original: ' . $message);
        } finally {
            self::$handling = false;
        }
    }

    private static function requestContext(): array
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($requestUri, PHP_URL_PATH);

        return [
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : 'UNKNOWN')),
            'path' => is_string($path) ? $path : '',
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'script' => (string) ($_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '')),
        ];
    }

    private static function actorContext(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user'])) {
            return ['user_id' => null, 'firm_id' => null];
        }

        return [
            'user_id' => isset($_SESSION['user']->id) ? (int) $_SESSION['user']->id : null,
            'firm_id' => isset($_SESSION['firm_id']) ? (int) $_SESSION['firm_id'] : null,
        ];
    }

    private static function sanitize($value, ?string $key = null, int $depth = 0)
    {
        if ($key !== null && preg_match('/pass(word)?|token|secret|authorization|cookie|csrf|api.?key/i', $key)) {
            return '[REDACTED]';
        }
        if ($depth > 4) {
            return '[MAX_DEPTH]';
        }
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $itemKey => $itemValue) {
                $clean[$itemKey] = self::sanitize($itemValue, (string) $itemKey, $depth + 1);
            }
            return $clean;
        }
        if (is_object($value)) {
            return self::sanitize(get_object_vars($value), $key, $depth + 1);
        }
        if (is_resource($value)) {
            return '[RESOURCE]';
        }
        if (is_string($value)) {
            return self::cleanMessage($value);
        }
        return $value;
    }

    private static function cleanMessage(string $message): string
    {
        $message = preg_replace('/[\\r\\n]+/', ' ', $message) ?? $message;
        return mb_substr($message, 0, 10000, 'UTF-8');
    }

    private static function severityName(int $severity): string
    {
        return in_array($severity, [E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING], true)
            ? 'warning'
            : (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT], true) ? 'notice' : 'error');
    }

    private static function createRequestId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable $exception) {
            return uniqid('req_', true);
        }
    }

    private static function isJsonRequest(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $script = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return strpos($accept, 'application/json') !== false
            || strpos($script, '/api/') !== false
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private static function errorSignature(int $severity, string $message, string $file, int $line): string
    {
        return hash('sha256', $severity . '|' . $message . '|' . $file . '|' . $line);
    }
}
