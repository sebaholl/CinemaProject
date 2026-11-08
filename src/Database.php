<?php
declare(strict_types=1);

final class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct(array $config)
    {
        $db = $config['db'] ?? [];
        foreach (['host', 'port', 'name', 'user', 'password'] as $key) {
            if (!array_key_exists($key, $db) || !is_scalar($db[$key])) {
                throw new InvalidArgumentException("Missing database configuration: {$key}");
            }
        }
        // Host and database name are DSN components, not bound SQL parameters.
        // Restrict them to ordinary host/name characters before constructing the DSN.
        if (!preg_match('/^[a-zA-Z0-9.:-]+$/D', (string)$db['host']) ||
            !preg_match('/^[a-zA-Z0-9_]+$/D', (string)$db['name']) ||
            filter_var($db['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
            throw new InvalidArgumentException('Invalid database host, port, or name');
        }
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
        $this->connection = new PDO($dsn, (string)$db['user'], (string)$db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->connection->exec("SET time_zone = '+00:00'");
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            $config ??= require dirname(__DIR__) . '/config/config.php';
            if (!is_array($config)) {
                throw new RuntimeException('Database configuration must return an array');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone() {}
    public function __wakeup(): void
    {
        throw new LogicException('Database cannot be unserialized');
    }
}
