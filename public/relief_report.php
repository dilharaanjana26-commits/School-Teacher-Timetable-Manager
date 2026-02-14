<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    'SELECT ra.*, at.name AS absent_teacher_name, rt.name AS relief_teacher_name, c.class_name, c.section, s.subject_name
     FROM relief_assignments ra
     INNER JOIN teachers at ON at.id = ra.absent_teacher_id
     INNER JOIN teachers rt ON rt.id = ra.relief_teacher_id
     INNER JOIN classes c ON c.id = ra.class_id
     INNER JOIN subjects s ON s.id = ra.subject_id
     WHERE ra.relief_date = :relief_date
     ORDER BY ra.day_of_week ASC, ra.period_number ASC'
);
$stmt->execute(['relief_date' => $date]);
$reportRows = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daily Relief Report</h3>
    <button class="btn btn-outline-secondary no-print" onclick="window.print()">Print / Save PDF</button>
</div>

<div class="card shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Report Date</label>
                <input type="date" class="form-control" name="date" value="<?= sanitize($date) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Relief Assignments for <?= sanitize($date) ?></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Period</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Absent Teacher</th>
                    <th>Relief Teacher</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$reportRows): ?>
                    <tr><td colspan="7" class="text-center text-muted">No relief assignments found.</td></tr>
                <?php endif; ?>
                <?php foreach ($reportRows as $row): ?>
                    <tr>
                        <td><?= sanitize($row['day_of_week']) ?></td>
                        <td><?= (int) $row['period_number'] ?></td>
                        <td><?= sanitize($row['class_name']) ?> <?= sanitize((string) $row['section']) ?></td>
                        <td><?= sanitize($row['subject_name']) ?></td>
                        <td><?= sanitize($row['absent_teacher_name']) ?></td>
                        <td><?= sanitize($row['relief_teacher_name']) ?></td>
                        <td><?= sanitize((string) $row['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
