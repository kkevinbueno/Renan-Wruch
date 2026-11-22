<?php
declare(strict_types=1);

/** Acesso ao banco via PDO com prepared statements (proteção contra injeção de SQL). */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }
        $driver = config('db.driver');
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if ($driver === 'sqlite') {
            $pdo = new PDO('sqlite:' . config('db.sqlite_path'), null, null, $opts);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', config('db.host'), config('db.port'), config('db.name'));
            $pdo = new PDO($dsn, config('db.user'), config('db.pass'), $opts);
            $pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        }
        return self::$pdo = $pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function driver(): string
    {
        return (string) config('db.driver');
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        foreach (array_values(array_is_list($params) ? $params : []) as $i => $v) {
            $st->bindValue($i + 1, $v, self::type($v));
        }
        if (!array_is_list($params)) {
            foreach ($params as $k => $v) {
                $st->bindValue(':' . ltrim((string) $k, ':'), $v, self::type($v));
            }
        }
        $st->execute();
        return $st;
    }

    private static function type(mixed $v): int
    {
        return match (true) {
            is_int($v) => PDO::PARAM_INT,
            is_bool($v) => PDO::PARAM_INT,
            $v === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function val(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function col(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(', ', array_map([self::class, 'ident'], $cols)) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        self::run($sql, array_map(fn($v) => is_bool($v) ? (int) $v : $v, array_values($data)));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $sets = implode(', ', array_map(fn($c) => self::ident($c) . ' = ?', array_keys($data)));
        $values = array_map(fn($v) => is_bool($v) ? (int) $v : $v, array_values($data));
        return self::run('UPDATE ' . self::ident($table) . " SET {$sets} WHERE {$where}", array_merge($values, $params))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run('DELETE FROM ' . self::ident($table) . " WHERE {$where}", $params)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function isUniqueViolation(Throwable $e): bool
    {
        $msg = $e->getMessage();
        return $e instanceof PDOException && (str_contains($msg, 'UNIQUE') || str_contains($msg, 'Duplicate entry') || ($e->errorInfo[1] ?? null) === 1062);
    }

    private static function ident(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Identificador inválido: ' . $name);
        }
        return $name;
    }

    /** Monta "IN (?, ?, ?)" para listas. */
    public static function in(array $values): string
    {
        return $values ? '(' . implode(',', array_fill(0, count($values), '?')) . ')' : '(NULL)';
    }
}
