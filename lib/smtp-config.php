<?php

/** Read SMTP configuration without opening a network connection. */
function loadBatchSmtpConfig($envPath = null)
{
    $envPath = $envPath ?? __DIR__ . '/../.env';
    if (!is_readable($envPath)) {
        throw new RuntimeException('SMTP configuration file is missing or unreadable.');
    }

    // RAW preserves passwords and avoids INI boolean/constant conversion.
    $env = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if ($env === false) {
        throw new RuntimeException('SMTP configuration file could not be parsed.');
    }
    foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION'] as $key) {
        if (!isset($env[$key]) || trim((string) $env[$key]) === '') {
            throw new RuntimeException('Missing SMTP configuration: ' . $key);
        }
    }
    if (strtolower(trim($env['MAIL_MAILER'] ?? 'smtp')) !== 'smtp') {
        throw new RuntimeException('MAIL_MAILER must be smtp.');
    }
    $port = filter_var($env['MAIL_PORT'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        throw new RuntimeException('MAIL_PORT must be a valid TCP port.');
    }
    $encryption = strtolower(trim($env['MAIL_ENCRYPTION']));
    $encryption = ['starttls' => 'tls', 'smtps' => 'ssl'][$encryption] ?? $encryption;
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        throw new RuntimeException('MAIL_ENCRYPTION must be tls or ssl.');
    }
    $debug = filter_var($env['MAIL_DEBUG'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4]]);
    if ($debug === false) {
        throw new RuntimeException('MAIL_DEBUG must be an integer from 0 to 4.');
    }

    return [
        'host' => trim($env['MAIL_HOST']),
        'port' => $port,
        'username' => $env['MAIL_USERNAME'],
        'password' => $env['MAIL_PASSWORD'],
        'secure' => $encryption,
        'auth' => true,
        'debug' => $debug,
    ];
}