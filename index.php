<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page = (string) ($_GET['page'] ?? 'dashboard');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect('login');
}

$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($method === 'POST' && !($page === 'track' && ($_POST['action'] ?? '') === 'lookup-ticket')) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'setup':
                if ($adminCount > 0) {
                    redirect('login');
                }
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $departmentNames = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($_POST['departments'] ?? '')) ?: []))));
                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10 || !$departmentNames) {
                    throw new RuntimeException('Enter an administrator name, a valid email, a password of at least 10 characters, and at least one department.');
                }
                $pdo->beginTransaction();
                foreach ($departmentNames as $departmentName) {
                    $stmt = $pdo->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
                    $stmt->execute([$departmentName]);
                }
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, password_hash) VALUES (?, ?, 'admin', ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $pdo->commit();
                flash('success', 'Administrator account created. Sign in to manage departments and tickets.');
                redirect('login');

            case 'register':
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $matric = strtoupper(trim((string) ($_POST['matric_number'] ?? '')));
                $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
                $password = (string) ($_POST['password'] ?? '');
                $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
                $departmentCheck->execute([$departmentId]);
                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $matric === '' || !$departmentCheck->fetchColumn() || strlen($password) < 8) {
                    throw new RuntimeException('Complete every field, choose a valid department, and use a password of at least 8 characters.');
                }
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, matric_number, department_id, role, password_hash) VALUES (?, ?, ?, ?, 'student', ?)");
                $stmt->execute([$name, $email, $matric, $departmentId, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', 'Your student account is ready. Please sign in.');
                redirect('login');

            case 'login':
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $stmt = $pdo->prepare('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.email = ? LIMIT 1');
                $stmt->execute([$email]);
                $account = $stmt->fetch();
                if (!$account || !password_verify($password, $account['password_hash'])) {
                    throw new RuntimeException('The email or password was not recognised.');
                }
                session_regenerate_id(true);
                unset($account['password_hash']);
                $_SESSION['user'] = $account;
                redirect('dashboard');

            case 'create-ticket':
                $student = require_role('student');
                $topic = trim((string) ($_POST['topic'] ?? ''));
                $subject = trim((string) ($_POST['subject'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $topics = ['Academic support', 'Fees and finance', 'Hostel and accommodation', 'Facilities and maintenance', 'ICT and student portal', 'Health and wellbeing', 'Harassment or safety', 'Other'];
                if (!in_array($topic, $topics, true) || $subject === '' || strlen($subject) > 180 || strlen($description) < 20) {
                    throw new RuntimeException('Choose a valid topic, enter a subject, and describe the issue in at least 20 characters.');
                }
                $reference = ticket_reference($pdo);
                $stmt = $pdo->prepare('INSERT INTO tickets (reference, student_id, topic, subject, description) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$reference, $student['id'], $topic, $subject, $description]);
                $ticketId = (int) $pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, status, message) VALUES (?, ?, ?, ?)');
                $stmt->execute([$ticketId, $student['id'], 'submitted', 'Support request submitted by student.']);
                flash('success', 'Ticket submitted. Save your reference: ' . $reference);
                redirect('ticket', ['id' => $ticketId]);

            case 'route-ticket':
                $admin = require_role('admin');
                $ticketId = filter_var($_POST['ticket_id'] ?? null, FILTER_VALIDATE_INT);
                $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
                $note = trim((string) ($_POST['message'] ?? ''));
                $department = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
                $department->execute([$departmentId]);
                if (!$ticketId || !$department->fetchColumn()) {
                    throw new RuntimeException('Select a valid department for this ticket.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE tickets SET department_id = ?, status = IF(status = 'submitted', 'assigned', status) WHERE id = ?");
                $stmt->execute([$departmentId, $ticketId]);
                if ($stmt->rowCount() === 0) {
                    $exists = $pdo->prepare('SELECT id FROM tickets WHERE id = ?');
                    $exists->execute([$ticketId]);
                    if (!$exists->fetchColumn()) {
                        throw new RuntimeException('Ticket not found.');
                    }
                }
                $ticket = $pdo->prepare('SELECT status FROM tickets WHERE id = ?');
                $ticket->execute([$ticketId]);
                $status = (string) $ticket->fetchColumn();
                $message = $note !== '' ? $note : 'Ticket routed to the selected department by an administrator.';
                $log = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, status, message) VALUES (?, ?, ?, ?)');
                $log->execute([$ticketId, $admin['id'], $status, $message]);
                $pdo->commit();
                flash('success', 'Ticket routed to the selected department.');
                redirect('ticket', ['id' => $ticketId]);

            case 'update-ticket':
                $staff = require_role('admin', 'department');
                $ticketId = filter_var($_POST['ticket_id'] ?? null, FILTER_VALIDATE_INT);
                $newStatus = (string) ($_POST['status'] ?? '');
                $message = trim((string) ($_POST['message'] ?? ''));
                $validStatuses = ['assigned', 'in_progress', 'resolved', 'closed'];
                $query = $pdo->prepare(ticket_query_base() . ' WHERE t.id = ?');
                $query->execute([$ticketId]);
                $ticket = $query->fetch();
                if (!$ticket || ($staff['role'] === 'department' && (int) $ticket['department_id'] !== (int) $staff['department_id'])) {
                    http_response_code(404);
                    exit('Ticket not found.');
                }
                if (!in_array($newStatus, $validStatuses, true) || $message === '') {
                    throw new RuntimeException('Choose a status and provide an update for the student.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $ticketId]);
                $stmt = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, status, message) VALUES (?, ?, ?, ?)');
                $stmt->execute([$ticketId, $staff['id'], $newStatus, $message]);
                $pdo->commit();
                flash('success', 'Ticket status and student update saved.');
                redirect('ticket', ['id' => $ticketId]);

            case 'create-department':
                require_role('admin');
                $name = trim((string) ($_POST['department_name'] ?? ''));
                $managerName = trim((string) ($_POST['manager_name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                if ($name === '' || $managerName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                    throw new RuntimeException('Enter the department name, manager name, valid email, and a password of at least 8 characters.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
                $stmt->execute([$name]);
                $departmentId = (int) $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, department_id, role, password_hash) VALUES (?, ?, ?, 'department', ?)");
                $stmt->execute([$managerName, $email, $departmentId, password_hash($password, PASSWORD_DEFAULT)]);
                $pdo->commit();
                flash('success', 'Department and department account created.');
                redirect('departments');

            default:
                throw new RuntimeException('This action is not available.');
        }
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            flash('danger', 'That email, matric number, or department name is already in use.');
        } else {
            error_log($exception->__toString());
            flash('danger', 'The request could not be saved. Check the database connection and try again.');
        }
        redirect($page);
    } catch (RuntimeException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('warning', $exception->getMessage());
        redirect($page);
    }
}

if ($adminCount === 0 && !in_array($page, ['setup', 'login', 'register', 'track'], true)) {
    redirect('setup');
}

$user = current_user();
if ($user && in_array($page, ['login', 'register', 'setup'], true)) {
    redirect('dashboard');
}
if ($page === 'setup' && $adminCount > 0) {
    redirect('login');
}
if ($page === 'dashboard' && !$user) {
    $page = 'welcome';
}
if ($page === 'ticket') {
    $ticketId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($user && !$ticketId) {
        redirect('tickets');
    }
}

$pageTitle = match ($page) {
    'setup' => 'First-time setup',
    'login' => 'Sign in',
    'register' => 'Create student account',
    'welcome' => 'Welcome',
    'new-ticket' => 'Create a ticket',
    'track' => 'Track a ticket',
    'ticket' => 'Ticket details',
    'tickets' => $user && $user['role'] === 'department' ? 'Department cases' : 'All tickets',
    'departments' => 'Departments',
    default => 'Dashboard',
};

if ($page === 'setup' || $page === 'login' || $page === 'register') {
    require __DIR__ . '/includes/auth-header.php';
    require __DIR__ . '/pages/auth.php';
    require __DIR__ . '/includes/auth-footer.php';
    exit;
}
if (!$user && $page === 'track') {
    require __DIR__ . '/includes/auth-header.php';
    require __DIR__ . '/pages/track.php';
    require __DIR__ . '/includes/auth-footer.php';
    exit;
}
if (!$user && $page !== 'welcome') {
    redirect('login');
}
if ($user) {
    $allowed = match ($user['role']) {
        'admin' => ['dashboard', 'tickets', 'ticket', 'departments'],
        'department' => ['dashboard', 'tickets', 'ticket'],
        default => ['dashboard', 'new-ticket', 'track', 'ticket'],
    };
    if (!in_array($page, $allowed, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/app.php';
require __DIR__ . '/includes/footer.php';
