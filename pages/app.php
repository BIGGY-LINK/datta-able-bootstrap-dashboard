<?php
switch ($page) {
    case 'welcome':
        ?>
        <section class="card welcome-card mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <span class="badge bg-white text-primary mb-3">FEDERAL COLLEGE OF WILDLIFE MANAGEMENT</span>
                        <h2 class="mb-2">A better way to get support.</h2>
                        <p class="mb-4">Tell us what you need, receive a unique reference, and follow your request through to resolution.</p>
                        <a href="index.php?page=register" class="btn btn-light text-primary me-2">Create student account</a>
                        <a href="index.php?page=login" class="btn btn-outline-light">Sign in</a>
                    </div>
                    <div class="col-lg-5">
                        <div class="welcome-photo-grid">
                            <figure class="welcome-photo mb-0">
                                <img src="assets/images/campus-building.jpg" alt="University campus building and green grounds" loading="lazy">
                                <figcaption>Campus photo via <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer">Unsplash</a></figcaption>
                            </figure>
                            <figure class="welcome-photo mb-0">
                                <img src="assets/images/school-students.jpg" alt="Students learning together in a classroom" loading="lazy">
                                <figcaption>Classroom photo via <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer">Unsplash</a></figcaption>
                            </figure>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="row g-3"><div class="col-md-4"><div class="card content-card h-100"><div class="card-body"><div class="stat-icon bg-primary-subtle text-primary mb-3"><i class="ph ph-list-check"></i></div><h5>Choose your topic</h5><p class="text-muted small mb-0">Tell us which area best describes your issue so it reaches the right support team.</p></div></div></div><div class="col-md-4"><div class="card content-card h-100"><div class="card-body"><div class="stat-icon bg-success-subtle text-success mb-3"><i class="ph ph-fingerprint"></i></div><h5>Keep your reference</h5><p class="text-muted small mb-0">Every submitted request receives a unique reference for checking its status.</p></div></div></div><div class="col-md-4"><div class="card content-card h-100"><div class="card-body"><div class="stat-icon bg-warning-subtle text-warning mb-3"><i class="ph ph-arrows-clockwise"></i></div><h5>Follow progress</h5><p class="text-muted small mb-0">Sign in or use your reference and student email to see the latest updates.</p></div></div></div></div>
        <?php
        break;

    case 'dashboard':
        $user = current_user();
        if ($user['role'] === 'student') {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status IN ('submitted','assigned','in_progress')) AS open_count, SUM(status = 'resolved') AS resolved_count FROM tickets WHERE student_id = ?");
            $stmt->execute([$user['id']]);
            $stats = $stmt->fetch();
            $stmt = $pdo->prepare(ticket_query_base() . ' WHERE t.student_id = ? ORDER BY t.created_at DESC LIMIT 8');
            $stmt->execute([$user['id']]);
        } elseif ($user['role'] === 'department') {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status IN ('assigned','in_progress')) AS open_count, SUM(status = 'resolved') AS resolved_count FROM tickets WHERE department_id = ?");
            $stmt->execute([$user['department_id']]);
            $stats = $stmt->fetch();
            $stmt = $pdo->prepare(ticket_query_base() . ' WHERE t.department_id = ? ORDER BY t.updated_at DESC LIMIT 8');
            $stmt->execute([$user['department_id']]);
        } else {
            $stats = $pdo->query("SELECT COUNT(*) AS total, SUM(department_id IS NULL) AS open_count, SUM(status = 'resolved') AS resolved_count FROM tickets")->fetch();
            $stmt = $pdo->query(ticket_query_base() . ' ORDER BY t.created_at DESC LIMIT 8');
        }
        $recentTickets = $stmt->fetchAll();
        $stats = array_map(static fn($v) => (int) ($v ?? 0), $stats);
        ?>
        <section class="card welcome-card mb-4"><div class="card-body p-4 d-flex align-items-center justify-content-between"><div><span class="badge bg-white text-primary mb-2"><?= e(ucfirst($user['role'])) ?> workspace</span><h2 class="mb-1">Hello, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h2><p class="mb-0"><?= $user['role'] === 'student' ? 'Your support requests and their latest progress are all here.' : ($user['role'] === 'admin' ? 'Manage requests and connect students with the right department.' : 'Review your department queue and keep students informed.') ?></p></div><i class="ph ph-hand-waving welcome-icon"></i></div></section>
        <div class="row g-3 mb-4">
            <?php foreach ([['All tickets', $stats['total'], 'ph-ticket', 'primary'], [$user['role'] === 'admin' ? 'Awaiting routing' : 'In progress', $stats['open_count'], 'ph-clock-countdown', 'warning'], ['Resolved', $stats['resolved_count'], 'ph-check-circle', 'success']] as [$label, $value, $icon, $color]): ?>
                <div class="col-md-4"><div class="card stat-card"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="stat-number"><?= $value ?></div><div class="stat-label"><?= e($label) ?></div></div><div class="stat-icon bg-<?= e($color) ?>-subtle text-<?= e($color) ?>"><i class="ph <?= e($icon) ?>"></i></div></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="card content-card"><div class="card-header d-flex justify-content-between align-items-center"><div><h5 class="mb-1">Recent tickets</h5><span class="small text-muted">The latest activity in your workspace</span></div><?php if ($user['role'] === 'student'): ?><a href="index.php?page=new-ticket" class="btn btn-primary btn-sm"><i class="ph ph-plus me-1"></i>New ticket</a><?php else: ?><a href="index.php?page=tickets" class="btn btn-light btn-sm">View all</a><?php endif; ?></div><div class="card-body p-0"><?php render_ticket_table($recentTickets, $user['role'] === 'student'); ?></div></div>
        <?php
        break;

    case 'new-ticket':
        $topics = ['Academic support', 'Fees and finance', 'Hostel and accommodation', 'Facilities and maintenance', 'ICT and student portal', 'Health and wellbeing', 'Harassment or safety', 'Other'];
        ?>
        <div class="row justify-content-center"><div class="col-xl-8"><div class="card content-card"><div class="card-header"><h5 class="mb-1">Tell us how we can help</h5><span class="small text-muted">A reference will be generated after you submit your request.</span></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create-ticket"><div class="mb-3"><label class="form-label">Issue topic</label><select class="form-select" name="topic" required><option value="">Select the closest topic</option><?php foreach ($topics as $topic): ?><option value="<?= e($topic) ?>"><?= e($topic) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Short subject</label><input class="form-control" name="subject" maxlength="180" required placeholder="Briefly summarise your request"></div><div class="mb-4"><label class="form-label">Describe your issue or request</label><textarea class="form-control" name="description" rows="7" minlength="20" required placeholder="Include useful details, dates, and what you have already tried."></textarea><div class="form-text">Please do not include passwords or sensitive personal information.</div></div><button class="btn btn-primary" type="submit">Submit support ticket</button> <a class="btn btn-light" href="index.php">Cancel</a></form></div></div></div></div>
        <?php
        break;

    case 'tickets':
        $user = current_user();
        if ($user['role'] === 'admin') {
            $tickets = $pdo->query(ticket_query_base() . ' ORDER BY t.created_at DESC')->fetchAll();
        } else {
            $stmt = $pdo->prepare(ticket_query_base() . ' WHERE t.department_id = ? ORDER BY t.created_at DESC');
            $stmt->execute([$user['department_id']]);
            $tickets = $stmt->fetchAll();
        }
        ?>
        <div class="card content-card"><div class="card-header"><h5 class="mb-1"><?= $user['role'] === 'department' ? 'Department cases' : 'All support tickets' ?></h5><span class="small text-muted"><?= count($tickets) ?> ticket<?= count($tickets) === 1 ? '' : 's' ?> in this view</span></div><div class="card-body p-0"><?php render_ticket_table($tickets, false, $user['role'] === 'admin'); ?></div></div>
        <?php
        break;

    case 'ticket':
        $user = current_user();
        $ticketId = (int) ($_GET['id'] ?? 0);
        $query = $pdo->prepare(ticket_query_base() . ' WHERE t.id = ?');
        $query->execute([$ticketId]);
        $ticket = $query->fetch();
        $ownsTicket = $ticket && ($user['role'] === 'admin' || ($user['role'] === 'student' && (int) $ticket['student_id'] === (int) $user['id']) || ($user['role'] === 'department' && (int) $ticket['department_id'] === (int) $user['department_id']));
        if (!$ownsTicket) {
            http_response_code(404);
            ?><div class="alert alert-warning">Ticket not found or you do not have access to view it.</div><?php
            break;
        }
        $stmt = $pdo->prepare('SELECT u.*, a.full_name AS author, a.role AS author_role FROM ticket_updates u JOIN users a ON a.id = u.user_id WHERE u.ticket_id = ? ORDER BY u.created_at DESC');
        $stmt->execute([$ticketId]);
        $updates = $stmt->fetchAll();
        ?>
        <div class="row g-4"><div class="col-lg-8"><div class="card content-card mb-4"><div class="card-header d-flex justify-content-between align-items-start gap-3"><div><span class="reference"><?= e($ticket['reference']) ?></span><h4 class="mt-2 mb-1"><?= e($ticket['subject']) ?></h4><span class="small text-muted"><?= e($ticket['topic']) ?> · Submitted <?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></span></div><span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e(status_label($ticket['status'])) ?></span></div><div class="card-body"><div class="ticket-description"><?= e($ticket['description']) ?></div></div></div>
            <div class="card content-card"><div class="card-header"><h5 class="mb-0">Updates</h5></div><div class="card-body"><div class="ticket-timeline"><?php foreach ($updates as $update): ?><div class="timeline-item"><span class="badge <?= e(status_class($update['status'])) ?>"><?= e(status_label($update['status'])) ?></span><p class="small mt-2 mb-1"><?= e($update['message']) ?></p><small class="text-muted"><?= e($update['author']) ?> · <?= e(date('M j, Y g:i a', strtotime($update['created_at']))) ?></small></div><?php endforeach; ?></div></div></div>
        </div><div class="col-lg-4"><div class="card content-card mb-4"><div class="card-header"><h5 class="mb-0">Request details</h5></div><div class="card-body"><div class="mb-3"><div class="ticket-detail-label">Student</div><strong><?= e($ticket['student_name']) ?></strong><div class="small text-muted"><?= e($ticket['matric_number']) ?> · <?= e($ticket['student_email']) ?></div></div><div class="mb-3"><div class="ticket-detail-label">Student department</div><strong><?= e($ticket['student_department'] ?: 'Not specified') ?></strong></div><div><div class="ticket-detail-label">Assigned department</div><strong><?= e($ticket['assigned_department'] ?: 'Awaiting assignment') ?></strong></div></div></div>
            <?php if ($user['role'] === 'admin'): $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll(); ?>
                <div class="card content-card mb-4"><div class="card-header"><h5 class="mb-0">Route ticket</h5></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="route-ticket"><input type="hidden" name="ticket_id" value="<?= $ticketId ?>"><div class="mb-3"><label class="form-label">Department</label><select name="department_id" class="form-select" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $ticket['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Note (optional)</label><textarea class="form-control" name="message" rows="3" placeholder="Add context for the department"></textarea></div><button class="btn btn-primary w-100">Route ticket</button></form></div></div>
            <?php endif; ?>
            <?php if ($user['role'] !== 'student'): ?>
                <div class="card content-card"><div class="card-header"><h5 class="mb-0">Post an update</h5></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update-ticket"><input type="hidden" name="ticket_id" value="<?= $ticketId ?>"><div class="mb-3"><label class="form-label">New status</label><select name="status" class="form-select" required><?php foreach (['assigned', 'in_progress', 'resolved', 'closed'] as $status): ?><option value="<?= e($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Message to student</label><textarea class="form-control" name="message" rows="4" required placeholder="Explain what is happening next"></textarea></div><button class="btn btn-primary w-100">Save update</button></form></div></div>
            <?php endif; ?>
        </div></div>
        <?php
        break;

    case 'departments':
        require_role('admin');
        $departments = $pdo->query('SELECT d.id, d.name, d.created_at, u.full_name AS manager_name, u.email FROM departments d LEFT JOIN users u ON u.department_id = d.id AND u.role = \'department\' ORDER BY d.name')->fetchAll();
        ?>
        <div class="row g-4"><div class="col-xl-7"><div class="card content-card"><div class="card-header"><h5 class="mb-1">College departments</h5><span class="small text-muted">Department staff can only see cases assigned to their own department.</span></div><div class="card-body p-0"><?php if ($departments): ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Department</th><th>Department account</th></tr></thead><tbody><?php foreach ($departments as $department): ?><tr><td class="fw-semibold"><?= e($department['name']) ?></td><td><?php if ($department['manager_name']): ?><?= e($department['manager_name']) ?><div class="small text-muted"><?= e($department['email']) ?></div><?php else: ?><span class="text-muted">No account yet</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-buildings"></i><h5>No departments yet</h5></div><?php endif; ?></div></div></div>
            <div class="col-xl-5"><div class="card content-card"><div class="card-header"><h5 class="mb-1">Add department & account</h5><span class="small text-muted">Create the department and its staff sign-in together.</span></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create-department"><div class="mb-3"><label class="form-label">Department name</label><input class="form-control" name="department_name" required></div><div class="mb-3"><label class="form-label">Staff full name</label><input class="form-control" name="manager_name" required></div><div class="mb-3"><label class="form-label">Staff email</label><input class="form-control" type="email" name="email" required></div><div class="mb-4"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" minlength="8" required><div class="form-text">Share it securely and have staff change it after signing in.</div></div><button class="btn btn-primary w-100">Create department account</button></form></div></div></div></div>
        <?php
        break;

    case 'track':
        ?>
        <div class="card content-card"><div class="card-body text-center py-5"><i class="ph ph-magnifying-glass text-primary" style="font-size:44px"></i><h4 class="mt-3">Track a submitted ticket</h4><p class="text-muted">Use the ticket reference and your student email to see updates.</p><a href="index.php?page=track" class="btn btn-primary">Open ticket tracker</a></div></div>
        <?php
        break;

    default:
        redirect('dashboard');
}

function render_ticket_table(array $tickets, bool $studentView, bool $showStudent = false): void
{
    if (!$tickets) {
        ?><div class="empty-state"><i class="ph ph-ticket"></i><h5>No tickets to show</h5><p class="small mb-3"><?= $studentView ? 'Submit your first request whenever you need support.' : 'Newly submitted requests will appear here.' ?></p><?php if ($studentView): ?><a href="index.php?page=new-ticket" class="btn btn-primary btn-sm">Create a ticket</a><?php endif; ?></div><?php
        return;
    }
    ?>
    <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Reference</th><th>Subject</th><?php if ($showStudent): ?><th>Student</th><?php endif; ?><th>Department</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
    <?php foreach ($tickets as $ticket): ?><tr><td><a class="reference text-decoration-none" href="index.php?page=ticket&id=<?= (int) $ticket['id'] ?>"><?= e($ticket['reference']) ?></a></td><td><a class="text-decoration-none text-dark fw-semibold" href="index.php?page=ticket&id=<?= (int) $ticket['id'] ?>"><?= e($ticket['subject']) ?></a><div class="small text-muted"><?= e($ticket['topic']) ?></div></td><?php if ($showStudent): ?><td><?= e($ticket['student_name']) ?><div class="small text-muted"><?= e($ticket['matric_number']) ?></div></td><?php endif; ?><td><?= e($ticket['assigned_department'] ?: 'Unassigned') ?></td><td><span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e(status_label($ticket['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php
}
