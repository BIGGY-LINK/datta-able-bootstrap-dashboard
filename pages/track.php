<?php
$ticket = null;
$updates = [];
$lookupError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lookup-ticket') {
    verify_csrf();
    $reference = strtoupper(trim((string) ($_POST['reference'] ?? '')));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $stmt = $pdo->prepare(ticket_query_base() . ' WHERE t.reference = ? AND s.email = ?');
    $stmt->execute([$reference, $email]);
    $ticket = $stmt->fetch();
    if ($ticket) {
        $stmt = $pdo->prepare('SELECT u.*, a.full_name AS author FROM ticket_updates u JOIN users a ON a.id = u.user_id WHERE u.ticket_id = ? ORDER BY u.created_at DESC');
        $stmt->execute([$ticket['id']]);
        $updates = $stmt->fetchAll();
    } else {
        $lookupError = 'No ticket matched that reference and email address.';
    }
}
?>
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <a href="index.php?page=login" class="auth-logo text-decoration-none text-dark"><span class="brand-mark">F</span><span>FCWM Student Support</span></a>
            <h1>Track a support request</h1>
            <p class="subtitle">Enter the ticket reference and the email address used when submitting it.</p>
            <?php if ($lookupError): ?><div class="alert alert-warning"><?= e($lookupError) ?></div><?php endif; ?>
            <form method="post" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="lookup-ticket">
                <div class="mb-3"><label class="form-label">Ticket reference</label><input class="form-control text-uppercase" name="reference" placeholder="FCWM-XXXXXXXXXX" required></div>
                <div class="mb-3"><label class="form-label">Student email</label><input class="form-control" name="email" type="email" required></div>
                <button class="btn btn-primary w-100">Find my ticket</button>
            </form>
            <?php if ($ticket): ?>
                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between gap-2"><strong><?= e($ticket['subject']) ?></strong><span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e(status_label($ticket['status'])) ?></span></div>
                    <div class="reference small mt-2"><?= e($ticket['reference']) ?></div>
                    <p class="small text-muted mt-2 mb-0">Topic: <?= e($ticket['topic']) ?> · Department: <?= e($ticket['assigned_department'] ?: 'Awaiting assignment') ?></p>
                </div>
                <div class="ticket-timeline"><?php foreach ($updates as $update): ?><div class="timeline-item"><span class="badge <?= e(status_class($update['status'])) ?>"><?= e(status_label($update['status'])) ?></span><p class="small mt-2 mb-1"><?= e($update['message']) ?></p><small class="text-muted"><?= e($update['author']) ?> · <?= e(date('M j, Y g:i a', strtotime($update['created_at']))) ?></small></div><?php endforeach; ?></div>
            <?php endif; ?>
            <p class="text-center small mt-4 mb-0"><a href="index.php?page=login">Return to sign in</a></p>
        </div>
    </div>
</div>
