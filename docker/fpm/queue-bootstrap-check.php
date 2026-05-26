<?php

declare(strict_types=1);

$connection = getenv('DB_CONNECTION') ?: 'mysql';

$requiredTables = array_values(array_unique(array_filter([
    'migrations',
    getenv('DB_CACHE_TABLE') ?: 'cache',
    getenv('DB_QUEUE_TABLE') ?: 'jobs',
    getenv('SESSION_TABLE') ?: 'sessions',
    'failed_jobs',
])));

try {
    if ($connection === 'sqlite') {
        $database = getenv('DB_DATABASE') ?: '/data/database.sqlite';

        if (! is_file($database)) {
            exit(1);
        }

        $pdo = new PDO(sprintf('sqlite:%s', $database));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        foreach ($requiredTables as $table) {
            $statement = $pdo->prepare(
                "select 1 from sqlite_master where type = 'table' and name = :name limit 1"
            );
            $statement->execute(['name' => $table]);

            if ($statement->fetchColumn() === false) {
                exit(1);
            }
        }

        exit(0);
    }

    if ($connection !== 'mysql') {
        fwrite(STDERR, sprintf("Unsupported DB_CONNECTION '%s' for queue bootstrap check.\n", $connection));
        exit(1);
    }

    $database = getenv('DB_DATABASE') ?: 'mailgun_proxy';
    $host = getenv('DB_HOST') ?: 'database';
    $port = getenv('DB_PORT') ?: '3306';
    $username = getenv('DB_USERNAME') ?: 'mailgun_proxy';
    $password = getenv('DB_PASSWORD') ?: '';

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    $placeholders = implode(', ', array_fill(0, count($requiredTables), '?'));
    $statement = $pdo->prepare(
        "select table_name from information_schema.tables where table_schema = ? and table_name in ($placeholders)"
    );
    $statement->execute([$database, ...$requiredTables]);

    $existingTables = $statement->fetchAll(PDO::FETCH_COLUMN);

    exit(count(array_unique($existingTables)) === count($requiredTables) ? 0 : 1);
} catch (Throwable) {
    exit(1);
}
