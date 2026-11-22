<?php
declare(strict_types=1);

/** Executa as migrações em app/migrations na ordem, registrando as já aplicadas. */
final class Migrator
{
    public static function sql(string $statement, string $driver): string
    {
        $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $engine = $driver === 'sqlite' ? '' : 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        return str_replace(['{PK}', '{ENGINE}'], [$pk, $engine], $statement);
    }

    public static function migrate(?callable $out = null): array
    {
        $out ??= fn($m) => null;
        $driver = DB::driver();
        DB::pdo()->exec(self::sql('CREATE TABLE IF NOT EXISTS migrations (id {PK}, name VARCHAR(190) NOT NULL, applied_at DATETIME NOT NULL) {ENGINE}', $driver));
        $applied = DB::col('SELECT name FROM migrations');
        $files = glob(APP_DIR . '/migrations/*.php');
        sort($files);
        $ran = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }
            $statements = require $file;
            // MySQL faz commit implícito em DDL; aplicamos em sequência e registramos ao final.
            foreach ($statements as $st) {
                DB::pdo()->exec(self::sql($st, $driver));
            }
            DB::insert('migrations', ['name' => $name, 'applied_at' => now()]);
            $out("Migração aplicada: {$name}");
            $ran[] = $name;
        }
        Seeder::essentials();
        return $ran;
    }

    /** Exporta o SQL equivalente para MySQL (para importar pelo phpMyAdmin, se preferir). */
    public static function exportMysql(): string
    {
        $sql = "-- Wruch Personal — esquema MySQL gerado a partir de app/migrations\n-- Importe pelo phpMyAdmin ou rode: php cron/migrate.php\n\n";
        $sql .= self::sql('CREATE TABLE IF NOT EXISTS migrations (id {PK}, name VARCHAR(190) NOT NULL, applied_at DATETIME NOT NULL) {ENGINE}', 'mysql') . ";\n\n";
        $files = glob(APP_DIR . '/migrations/*.php');
        sort($files);
        foreach ($files as $file) {
            foreach (require $file as $st) {
                $sql .= self::sql($st, 'mysql') . ";\n\n";
            }
            $sql .= "INSERT INTO migrations (name, applied_at) VALUES ('" . basename($file, '.php') . "', UTC_TIMESTAMP());\n\n";
        }
        foreach (Modalities::DEFAULTS as $i => $m) {
            $sql .= sprintf("INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('%s', '%s', '%s', 1, %d);\n", $m['slug'], addslashes($m['name']), json_encode($m['fields']), $i);
        }
        return $sql;
    }
}
