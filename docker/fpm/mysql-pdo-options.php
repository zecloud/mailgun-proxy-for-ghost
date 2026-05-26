<?php

declare(strict_types=1);

/**
 * Build PDO options for MySQL connections using the same SSL env var as Laravel.
 *
 * @return array<int, mixed>
 */
function buildMysqlPdoOptions(): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    $sslCa = getenv('MYSQL_ATTR_SSL_CA');

    if (! is_string($sslCa) || trim($sslCa) === '') {
        return $options;
    }

    $sslCa = trim($sslCa);
    $sslCaAttribute = null;

    if (defined('Pdo\\Mysql::ATTR_SSL_CA')) {
        $sslCaAttribute = constant('Pdo\\Mysql::ATTR_SSL_CA');
    } elseif (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $sslCaAttribute = constant('PDO::MYSQL_ATTR_SSL_CA');
    }

    if ($sslCaAttribute !== null) {
        $options[$sslCaAttribute] = $sslCa;
    }

    return $options;
}
