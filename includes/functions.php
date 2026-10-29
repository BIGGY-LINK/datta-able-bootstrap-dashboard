<?php
declare(strict_types=1);

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $server = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $connection = new PDO($server, DB_USER, DB_PASS, $options);
    $connection->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $connection->exec('USE `' . str_replace('`', '``', DB_NAME) . '`');
    $connection->exec("
        CREATE TABLE IF NOT EXISTS departments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $connection->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            matric_number VARCHAR(50) NULL UNIQUE,
            department_id INT UNSIGNED NULL,
            role ENUM('student','admin','department') NOT NULL DEFAULT 'student',
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    $connection->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reference VARCHAR(24) NOT NULL UNIQUE,
            student_id INT UNSIGNED NOT NULL,
            department_id INT UNSIGNED NULL,
            topic VARCHAR(100) NOT NULL,
            subject VARCHAR(180) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('submitted','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'submitted',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_tickets_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_tickets_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
            INDEX idx_tickets_department_status (department_id, status),
            INDEX idx_tickets_student_created (student_id, created_at)
        ) ENGINE=InnoDB
    ");
    $connection->exec("
        CREATE TABLE IF NOT EXISTS ticket_updates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id BIGINT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            status ENUM('submitted','assigned','in_progress','resolved','closed') NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_updates_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            CONSTRAINT fk_updates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_updates_ticket_created (ticket_id, created_at)
        ) ENGINE=InnoDB
    ");

    return $pdo = $connection;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $page, array $params = []): never
{
    $params = ['page' => $page] + $params;
    header('Location: index.php?' . http_build_query($params));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('The form expired. Please go back, refresh, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_role(string ...$roles): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        redirect('login');
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
    return $user;
}

function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function status_class(string $status): string
{
    return match ($status) {
        'submitted' => 'text-bg-secondary',
        'assigned' => 'text-bg-info',
        'in_progress' => 'text-bg-warning',
        'resolved' => 'text-bg-success',
        'closed' => 'text-bg-dark',
        default => 'text-bg-secondary',
    };
}

function ticket_reference(PDO $pdo): string
{
    do {
        $reference = 'FCWM-' . strtoupper(bin2hex(random_bytes(5)));
        $check = $pdo->prepare('SELECT 1 FROM tickets WHERE reference = ?');
        $check->execute([$reference]);
    } while ($check->fetchColumn());
    return $reference;
}

function ticket_query_base(): string
{
    return 'SELECT t.*, s.full_name AS student_name, s.email AS student_email, s.matric_number,
                   sd.name AS student_department, d.name AS assigned_department
            FROM tickets t
            JOIN users s ON s.id = t.student_id
            LEFT JOIN departments sd ON sd.id = s.department_id
            LEFT JOIN departments d ON d.id = t.department_id';
}
