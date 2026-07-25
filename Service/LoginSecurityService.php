<?php

namespace Service;

class LoginSecurityService
{
    public function __construct(private \PDO $db) {}

    public function isBlocked(string $identifier, string $ip): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_security_attempts
             WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
               AND success = 0 AND (identifier_hash = ? OR ip_address = ?)"
        );
        $stmt->execute([$this->hashIdentifier($identifier), $ip]);
        return (int) $stmt->fetchColumn() >= 5;
    }

    public function record(string $identifier, string $ip, bool $success): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO login_security_attempts (identifier_hash, ip_address, success) VALUES (?, ?, ?)"
        );
        $stmt->execute([$this->hashIdentifier($identifier), $ip, $success ? 1 : 0]);
    }

    public function clearFailures(string $identifier, string $ip): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM login_security_attempts WHERE identifier_hash = ? OR ip_address = ?"
        );
        $stmt->execute([$this->hashIdentifier($identifier), $ip]);
    }

    public function event(?int $userId, string $eventType, string $description): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO security_events (user_id, event_type, ip_address, user_agent, description)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $eventType,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
            $description,
        ]);
    }

    private function hashIdentifier(string $identifier): string
    {
        return hash('sha256', mb_strtolower(trim($identifier)));
    }
}
