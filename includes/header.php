<?php
$user = current_user();
$role = $user['role'] ?? 'guest';
$pageTitle = $pageTitle ?? 'Dashboard';
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="icon" href="dist/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="dist/assets/fonts/phosphor/regular/style.css">
    <link rel="stylesheet" href="dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="dist/assets/css/style.css">
    <link rel="stylesheet" href="dist/assets/css/style-preset.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme="light">
<?php if ($user): ?>
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="index.php" class="b-brand text-primary app-brand">
                    <span class="brand-mark">F</span><span>FCWM <small>Student Support</small></span>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar">
                    <li class="pc-item pc-caption"><label><?= e(ucfirst($role)) ?> workspace</label></li>
                    <?php
                    $menus = match ($role) {
                        'admin' => [
                            ['dashboard', 'Overview', 'ph ph-squares-four'],
                            ['tickets', 'All tickets', 'ph ph-ticket'],
                            ['departments', 'Departments', 'ph ph-buildings'],
                        ],
                        'department' => [
                            ['dashboard', 'Overview', 'ph ph-squares-four'],
                            ['tickets', 'Department cases', 'ph ph-ticket'],
                        ],
                        default => [
                            ['dashboard', 'My dashboard', 'ph ph-squares-four'],
                            ['new-ticket', 'Create a ticket', 'ph ph-plus-circle'],
                            ['track', 'Track a ticket', 'ph ph-magnifying-glass'],
                        ],
                    };
                    foreach ($menus as [$menuPage, $label, $icon]):
                    ?>
                        <li class="pc-item <?= (($page ?? '') === $menuPage) ? 'active' : '' ?>">
                            <a href="index.php?page=<?= e($menuPage) ?>" class="pc-link">
                                <span class="pc-micon"><i class="<?= e($icon) ?>"></i></span>
                                <span class="pc-mtext"><?= e($label) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="pc-item pc-caption"><label>Account</label></li>
                    <li class="pc-item">
                        <a href="index.php?page=logout" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-sign-out"></i></span>
                            <span class="pc-mtext">Sign out</span>
                        </a>
                    </li>
                </ul>
                <div class="sidebar-support">
                    <span class="support-icon"><i class="ph ph-chats-circle"></i></span>
                    <h6>Here to help</h6>
                    <p>Submit a support request and follow its progress from your workspace.</p>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
<header class="pc-header">
    <div class="header-wrapper">
        <div class="me-auto d-flex align-items-center gap-3">
            <?php if ($user): ?>
                <button class="pc-h-item btn btn-link p-0 menu-toggle" type="button" aria-label="Toggle navigation"><i class="ph ph-list"></i></button>
            <?php endif; ?>
            <div class="header-title">
                <span><?= e(match ($role) { 'admin' => 'Administration', 'department' => 'Department desk', 'student' => 'Student portal', default => 'Support portal' }) ?></span>
                <strong><?= e($pageTitle) ?></strong>
            </div>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <?php if ($user): ?>
                <div class="user-chip"><span class="user-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span><span><strong><?= e($user['full_name']) ?></strong><small><?= e(ucfirst($role)) ?></small></span></div>
            <?php else: ?>
                <a class="btn btn-light" href="index.php?page=login">Sign in</a>
                <a class="btn btn-primary" href="index.php?page=register">Create account</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="pc-container">
    <div class="pc-content">
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= e($flashMessage['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
