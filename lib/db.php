<?php

/** Shared SQL Server connection using the batch application's existing PDO_ODBC driver. */
function getDatabaseConnection()
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $envPath = __DIR__ . '/../.env';
    if (!is_readable($envPath)) {
        throw new RuntimeException('Database configuration file is missing or unreadable.');
    }
    $env = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if ($env === false) {
        throw new RuntimeException('Database configuration file could not be parsed.');
    }
    foreach (['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
        if (!isset($env[$key]) || trim((string) $env[$key]) === '') {
            throw new RuntimeException('Missing database configuration: ' . $key);
        }
    }
    // DB_CONNECTION describes the database family; retain PDO_ODBC for existing batches.
    if (!in_array(strtolower(trim($env['DB_CONNECTION'] ?? 'sqlsrv')), ['sqlsrv', 'odbc'], true)) {
        throw new RuntimeException('DB_CONNECTION must be sqlsrv or odbc for this batch connection.');
    }
    if (!in_array('odbc', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('The PDO_ODBC extension is required.');
    }
    $port = filter_var($env['DB_PORT'] ?? '1433', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        throw new RuntimeException('DB_PORT must be a valid TCP port.');
    }
    $server = trim($env['DB_HOST']) . ',' . $port;
    $database = trim($env['DB_DATABASE']);
    // Escape ODBC attribute values; credentials are passed separately, never embedded in the DSN.
    $quote = static function ($value) {
        return '{' . str_replace('}', '}}', $value) . '}';
    };
    $dsn = 'odbc:Driver={SQL Server};Server=' . $quote($server) . ';Database=' . $quote($database) . ';';
    // DB_TRUST_SERVER_CERTIFICATE is not mapped to this legacy driver. Changing TLS policy
    // requires a separate migration to a modern SQL Server ODBC driver.
    try {
        $connection = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $error) {
        // Do not expose connection strings or credentials in web responses or scheduler output.
        throw new RuntimeException('Database connection failed; check environment settings, ODBC driver and network access.');
    }
    return $connection;
}