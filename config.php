<?php
declare(strict_types=1);

define('APP_NAME', 'FCWM Student Support');
define('DB_HOST', getenv('FCWM_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('FCWM_DB_PORT') ?: '3306');
define('DB_NAME', getenv('FCWM_DB_NAME') ?: 'fcwm_student_support');
define('DB_USER', getenv('FCWM_DB_USER') ?: 'root');
define('DB_PASS', getenv('FCWM_DB_PASS') ?: '');
