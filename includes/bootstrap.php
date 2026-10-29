<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $pdo = database();
} catch (PDOException $exception) {
    error_log($exception->__toString());
    http_response_code(503);
    exit(
        '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
        '<title>Database unavailable | ' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') . '</title>' .
        '<body style="font:16px/1.6 system-ui,sans-serif;max-width:680px;margin:12vh auto;padding:24px;color:#172b4d">' .
        '<h1>Student support database unavailable</h1>' .
        '<p>The application could not connect to MySQL or create its database. Start MySQL in the XAMPP Control Panel, then reload this page.</p>' .
        '<p>Default XAMPP settings use host <code>127.0.0.1</code>, database <code>fcwm_student_support</code>, user <code>root</code>, and a blank password. If your MySQL credentials differ, set the <code>FCWM_DB_HOST</code>, <code>FCWM_DB_PORT</code>, <code>FCWM_DB_NAME</code>, <code>FCWM_DB_USER</code>, and <code>FCWM_DB_PASS</code> environment variables.</p>' .
        '<p>The MySQL account must be allowed to create the database and tables.</p></body></html>'
    );
}
