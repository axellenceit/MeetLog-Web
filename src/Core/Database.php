<?php
declare(strict_types=1);

namespace Src\Core;

use PDO;
use PDOException;
use PDOStatement;

final class Database
{
    private PDO $pdo;

    public function __construct(?Config $config = null)
    {
        if ($config === null) {
            $config = new Config(dirname(__DIR__, 2) . '/meetlog.ini');
        }

        $host = (string) $config->get('database', 'host', '127.0.0.1');
        $dbname = (string) $config->get('database', 'name', '');
        $user = (string) $config->get('database', 'user', '');
        $pass = (string) $config->get('database', 'pass', '');
        $charset = (string) $config->get('database', 'charset', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function statement(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function query(string $sql, array $params = []): array
    {
        return $this->statement($sql, $params)->fetchAll();
    }

    public function first(string $sql, array $params = []): ?array
    {
        $result = $this->statement($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    public function execute(string $sql, array $params = []): bool
    {
        return $this->statement($sql, $params)->rowCount() >= 0;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}