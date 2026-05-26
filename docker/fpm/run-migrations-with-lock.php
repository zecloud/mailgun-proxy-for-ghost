<?php

declare(strict_types=1);

require_once __DIR__.'/mysql-pdo-options.php';

$runMigrations = static function (): int {
    passthru(escapeshellarg(PHP_BINARY).' artisan migrate --force', $exitCode);

    return (int) $exitCode;
};

$connection = getenv('DB_CONNECTION') ?: 'mysql';

if ($connection !== 'mysql') {
    exit($runMigrations());
}

$database = getenv('DB_DATABASE') ?: 'mailgun_proxy';
$host = getenv('DB_HOST') ?: 'database';
$port = getenv('DB_PORT') ?: '3306';
$username = getenv('DB_USERNAME') ?: 'mailgun_proxy';
$password = getenv('DB_PASSWORD') ?: '';
$lockTimeout = max(1, (int) (getenv('MIGRATION_LOCK_TIMEOUT') ?: 120));
$lockName = getenv('MIGRATION_LOCK_NAME') ?: sprintf('%s:migrations', $database);
$lockAcquired = false;
$exitCode = 1;

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
        $username,
        $password,
        buildMysqlPdoOptions()
    );

    $statement = $pdo->prepare('select get_lock(?, ?)');
    $statement->execute([$lockName, $lockTimeout]);
    $lockAcquired = (int) $statement->fetchColumn() === 1;

    if (! $lockAcquired) {
        fwrite(STDERR, sprintf("Timed out waiting for migration lock '%s'.\n", $lockName));
    } else {
        $exitCode = $runMigrations();
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "Failed to run migrations with lock: %s: %s\n",
            $exception::class,
            $exception->getMessage()
        )
    );
} finally {
    if ($lockAcquired) {
        try {
            $statement = $pdo->prepare('select release_lock(?)');
            $statement->execute([$lockName]);
        } catch (Throwable $exception) {
            fwrite(
                STDERR,
                sprintf(
                    "Failed to release migration lock: %s: %s\n",
                    $exception::class,
                    $exception->getMessage()
                )
            );
        }
    }
}

exit($exitCode);
