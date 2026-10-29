<?php
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$flashMessage = take_flash();
?>
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <a href="index.php" class="auth-logo text-decoration-none text-dark"><span class="brand-mark">F</span><span>Federal College of Wildlife Management<br><small class="text-muted fw-normal">Student Support Portal</small></span></a>
            <?php if ($flashMessage): ?><div class="alert alert-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div><?php endif; ?>
            <?php if ($page === 'setup'): ?>
                <h1>Set up your support portal</h1>
                <p class="subtitle">Create the first administrator and add your college departments. The database and tables are created automatically.</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="setup">
                    <div class="mb-3"><label class="form-label">Administrator full name</label><input class="form-control" name="full_name" required autocomplete="name"></div>
                    <div class="mb-3"><label class="form-label">Administrator email</label><input class="form-control" name="email" type="email" required autocomplete="email"></div>
                    <div class="mb-3"><label class="form-label">Password</label><input class="form-control" name="password" type="password" minlength="10" required autocomplete="new-password"><div class="form-text">Use at least 10 characters.</div></div>
                    <div class="mb-4"><label class="form-label">Departments</label><textarea class="form-control" name="departments" rows="4" required>Wildlife Management</textarea><div class="form-text">One department per line. You can add more departments later.</div></div>
                    <button class="btn btn-primary w-100" type="submit">Create administrator</button>
                </form>
            <?php elseif ($page === 'register'): ?>
                <h1>Create your student account</h1>
                <p class="subtitle">Use your college details to submit requests and follow their progress.</p>
                <?php if (!$departments): ?><div class="alert alert-warning">The college has not added any departments yet. Please contact the portal administrator.</div><?php else: ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" required autocomplete="name"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required autocomplete="email"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Matric number</label><input class="form-control" name="matric_number" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Department</label><select class="form-select" name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>"><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-4"><label class="form-label">Password</label><input class="form-control" name="password" type="password" minlength="8" required autocomplete="new-password"><div class="form-text">Use at least 8 characters.</div></div>
                        <button class="btn btn-primary w-100" type="submit">Create account</button>
                    </form>
                <?php endif; ?>
                <p class="text-center small text-muted mt-4 mb-0">Already registered? <a href="index.php?page=login">Sign in</a></p>
            <?php else: ?>
                <h1>Welcome back</h1>
                <p class="subtitle">Sign in to your student, department, or administrator workspace.</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3"><label class="form-label">Email address</label><input class="form-control" name="email" type="email" required autocomplete="email"></div>
                    <div class="mb-4"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required autocomplete="current-password"></div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
                <div class="d-flex justify-content-between small mt-4"><a href="index.php?page=register">Create student account</a><a href="index.php?page=track">Track a ticket</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
