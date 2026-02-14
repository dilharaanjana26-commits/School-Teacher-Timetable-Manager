<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $absenceDate = $_POST['absence_date'] ?? date('Y-m-d');
    $dayOfWeek = $_POST['day_of_week'] ?? '';
    $periodNumber = (int) ($_POST['period_number'] ?? 1);
    $reason = trim($_POST['reason'] ?? '');

    try {
        $stmt = $pdo->prepare('INSERT INTO absences (teacher_id, absence_date, day_of_week, period_number, reason) VALUES (:teacher_id, :absence_date, :day_of_week, :period_number, :reason)');
        $stmt->execute([
            'teacher_id' => $teacherId,
            'absence_date' => $absenceDate,
            'day_of_week' => $dayOfWeek,
            'period_number' => $periodNumber,
            'reason' => $reason ?: null,
        ]);

        $absenceId = (int) $pdo->lastInsertId();
        $assigned = autoAssignRelief($pdo, $absenceId);

        if ($assigned) {
            setFlash('Absence marked and relief assigned to ' . $assigned['name'] . '.');
        } else {
            setFlash('Absence marked, but no suitable relief teacher was available.', 'warning');
        }
    } catch (Throwable $e) {
        setFlash('Failed to mark absence: ' . $e->getMessage(), 'danger');
    }

    header('Location: absences.php');
    exit;
}

$teachers = $pdo->query('SELECT id, name FROM teachers WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
$recentAbsences = $pdo->query(
    'SELECT a.*, t.name AS teacher_name
     FROM absences a
     INNER JOIN teachers t ON t.id = a.teacher_id
     ORDER BY a.created_at DESC
     LIMIT 20'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3">Mark Teacher Absent</h3>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-select" required>
                    <option value="">Select teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>"><?= sanitize($teacher['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="absence_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Day</label>
                <select name="day_of_week" class="form-select" required>
                    <?php foreach (daysOfWeek() as $day): ?>
                        <option value="<?= $day ?>"><?= $day ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Period</label>
                <select name="period_number" class="form-select" required>
                    <?php foreach (periods() as $period): ?>
                        <option value="<?= $period ?>">Period <?= $period ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="Sick leave, training, etc.">
            </div>
            <div class="col-12 d-grid d-md-flex justify-content-md-end mt-2">
                <button class="btn btn-danger" type="submit">Mark Absent & Auto Assign Relief</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Recent Absences</div>
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Date</th><th>Teacher</th><th>Day</th><th>Period</th><th>Reason</th></tr></thead>
            <tbody>
                <?php foreach ($recentAbsences as $absence): ?>
                    <tr>
                        <td><?= sanitize($absence['absence_date']) ?></td>
                        <td><?= sanitize($absence['teacher_name']) ?></td>
                        <td><?= sanitize($absence['day_of_week']) ?></td>
                        <td><?= (int) $absence['period_number'] ?></td>
                        <td><?= sanitize((string) $absence['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
