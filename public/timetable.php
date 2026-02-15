<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $params = [
            'class_id' => (int) ($_POST['class_id'] ?? 0),
            'subject_id' => (int) ($_POST['subject_id'] ?? 0),
            'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
            'day_of_week' => $_POST['day_of_week'] ?? '',
            'period_number' => (int) ($_POST['period_number'] ?? 1),
        ];

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE timetable SET class_id=:class_id, subject_id=:subject_id, teacher_id=:teacher_id, day_of_week=:day_of_week, period_number=:period_number WHERE id=:id');
            $params['id'] = $id;
            $stmt->execute($params);
            setFlash('Timetable entry updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, period_number) VALUES (:class_id, :subject_id, :teacher_id, :day_of_week, :period_number)');
            $stmt->execute($params);
            setFlash('Timetable entry created.');
        }
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM timetable WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('Timetable entry deleted.');
    }

    header('Location: timetable.php?view=class&class_id=' . (int) ($_POST['class_id'] ?? 0));
    exit;
}

$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC')->fetchAll();
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC')->fetchAll();
$teachers = $pdo->query('SELECT id, name FROM teachers WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
$viewMode = $_GET['view'] ?? 'class';
if (!in_array($viewMode, ['class', 'teacher'], true)) {
    $viewMode = 'class';
}

$selectedClassId = (int) ($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selectedTeacherId = (int) ($_GET['teacher_id'] ?? ($teachers[0]['id'] ?? 0));

$timetableSql = 'SELECT tt.*, s.subject_name, t.name AS teacher_name, c.class_name
    FROM timetable tt
    INNER JOIN subjects s ON s.id = tt.subject_id
    INNER JOIN teachers t ON t.id = tt.teacher_id
    INNER JOIN classes c ON c.id = tt.class_id';

if ($viewMode === 'teacher') {
    $timetableStmt = $pdo->prepare($timetableSql . ' WHERE tt.teacher_id = :teacher_id');
    $timetableStmt->execute(['teacher_id' => $selectedTeacherId]);
} else {
    $timetableStmt = $pdo->prepare($timetableSql . ' WHERE tt.class_id = :class_id');
    $timetableStmt->execute(['class_id' => $selectedClassId]);
}
$entries = $timetableStmt->fetchAll();

$map = [];
foreach ($entries as $entry) {
    $map[$entry['day_of_week']][(int) $entry['period_number']] = $entry;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Weekly Timetable</h3>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">View Mode</label>
                <select class="form-select" name="view" onchange="this.form.submit()">
                    <option value="class" <?= $viewMode === 'class' ? 'selected' : '' ?>>Class Timetable</option>
                    <option value="teacher" <?= $viewMode === 'teacher' ? 'selected' : '' ?>>Teacher Timetable</option>
                </select>
            </div>

            <?php if ($viewMode === 'teacher'): ?>
                <div class="col-md-4">
                    <label class="form-label">Select Teacher</label>
                    <select class="form-select" name="teacher_id" onchange="this.form.submit()">
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= (int) $teacher['id'] ?>" <?= $selectedTeacherId === (int) $teacher['id'] ? 'selected' : '' ?>>
                                <?= sanitize($teacher['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Select Class</label>
                    <select class="form-select" name="class_id" onchange="this.form.submit()">
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>>
                                <?= sanitize($class['class_name']) ?> <?= sanitize((string) $class['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead>
                <tr>
                    <th>Day / Period</th>
                    <?php foreach (periods() as $period): ?>
                        <th>P<?= $period ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (daysOfWeek() as $day): ?>
                    <tr>
                        <th><?= $day ?></th>
                        <?php foreach (periods() as $period):
                            $slot = $map[$day][$period] ?? null; ?>
                            <td>
                                <?php if ($viewMode === 'teacher'): ?>
                                    <?php if ($slot): ?>
                                        <div><strong><?= sanitize($slot['subject_name']) ?></strong></div>
                                        <div class="small text-muted"><?= sanitize($slot['class_name']) ?></div>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Free Period</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($slot): ?>
                                        <div><strong><?= sanitize($slot['subject_name']) ?></strong></div>
                                        <div class="small text-muted"><?= sanitize($slot['teacher_name']) ?></div>
                                        <button class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="collapse" data-bs-target="#edit-slot-<?= (int) $slot['id'] ?>">Edit</button>
                                        <form method="post" class="mt-1">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $slot['id'] ?>">
                                            <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                                            <button class="btn btn-sm btn-outline-danger confirm-delete" type="submit">Delete</button>
                                        </form>
                                        <div class="collapse mt-2" id="edit-slot-<?= (int) $slot['id'] ?>">
                                            <form method="post" class="text-start">
                                                <input type="hidden" name="action" value="save">
                                                <input type="hidden" name="id" value="<?= (int) $slot['id'] ?>">
                                                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                                                <input type="hidden" name="day_of_week" value="<?= $day ?>">
                                                <input type="hidden" name="period_number" value="<?= $period ?>">
                                                <select name="subject_id" class="form-select form-select-sm mb-1" required>
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <option value="<?= (int) $subject['id'] ?>" <?= (int) $slot['subject_id'] === (int) $subject['id'] ? 'selected' : '' ?>><?= sanitize($subject['subject_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="teacher_id" class="form-select form-select-sm mb-1" required>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <option value="<?= (int) $teacher['id'] ?>" <?= (int) $slot['teacher_id'] === (int) $teacher['id'] ? 'selected' : '' ?>><?= sanitize($teacher['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-success btn-sm w-100">Save</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#new-slot-<?= $day . '-' . $period ?>">Add</button>
                                        <div class="collapse mt-2" id="new-slot-<?= $day . '-' . $period ?>">
                                            <form method="post" class="text-start">
                                                <input type="hidden" name="action" value="save">
                                                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                                                <input type="hidden" name="day_of_week" value="<?= $day ?>">
                                                <input type="hidden" name="period_number" value="<?= $period ?>">
                                                <select name="subject_id" class="form-select form-select-sm mb-1" required>
                                                    <option value="">Select subject</option>
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <option value="<?= (int) $subject['id'] ?>"><?= sanitize($subject['subject_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="teacher_id" class="form-select form-select-sm mb-1" required>
                                                    <option value="">Select teacher</option>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <option value="<?= (int) $teacher['id'] ?>"><?= sanitize($teacher['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-primary btn-sm w-100">Save</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
