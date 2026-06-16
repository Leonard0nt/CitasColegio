<?php

if (!defined('CITAS_CSF_ENTRY')) {
    http_response_code(403);
    exit;
}

function configure_bulk_import_runtime(): void
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }
}

function bulk_import_password_hash(string $password): string
{
    static $hashes = [];

    if (!array_key_exists($password, $hashes)) {
        // Bulk CSV imports can create tens of thousands of users in one request.
        // Imported accounts use a short initial password derived from the RUT, so
        // use the lowest bcrypt cost and cache repeated 4-digit passwords to keep
        // the upload under the web server execution limit.
        $hashes[$password] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
    }

    return $hashes[$password];
}
