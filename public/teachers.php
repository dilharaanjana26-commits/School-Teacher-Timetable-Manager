<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$teacherSections = [
    'Primary(1-5)',
    'Secondary(6-11)',
    'A Level(12 & 13)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $section = trim($_POST['section'] ?? '');
        $maxPeriods = max(1, (int) ($_POST['max_periods_per_day'] ?? 6));
        $subjectIds = $_POST['subject_ids'] ?? [];

        if (!in_array($section, $teacherSections, true)) {
            setFlash('Please select a valid teacher section.', 'danger');
            header('Location: teachers.php');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO teachers (name, email, phone, section, max_periods_per_day) VALUES (:name, :email, :phone, :section, :max_periods)');
            $stmt->execute([
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'section' => $section,
                'max_periods' => $maxPeriods,
            ]);
            $teacherId = (int) $pdo->lastInsertId();

            $insertSub = $pdo->prepare('INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id) VALUES (:teacher_id, :subject_id)');
            foreach ($subjectIds as $subjectId) {
                $insertSub->execute(['teacher_id' => $teacherId, 'subject_id' => (int) $subjectId]);
            }
            $pdo->commit();
            setFlash('Teacher created successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('Failed to create teacher: ' . $e->getMessage(), 'danger');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $section = trim($_POST['section'] ?? '');
        $maxPeriods = max(1, (int) ($_POST['max_periods_per_day'] ?? 6));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $subjectIds = $_POST['subject_ids'] ?? [];

        if (!in_array($section, $teacherSections, true)) {
            setFlash('Please select a valid teacher section.', 'danger');
            header('Location: teachers.php');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE teachers SET name=:name, email=:email, phone=:phone, section=:section, max_periods_per_day=:max_periods, is_active=:is_active WHERE id=:id');
            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'section' => $section,
                'max_periods' => $maxPeriods,
                'is_active' => $isActive,
            ]);

            $pdo->prepare('DELETE FROM teacher_subjects WHERE teacher_id = :teacher_id')->execute(['teacher_id' => $id]);
            $insertSub = $pdo->prepare('INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id) VALUES (:teacher_id, :subject_id)');
            foreach ($subjectIds as $subjectId) {
                $insertSub->execute(['teacher_id' => $id, 'subject_id' => (int) $subjectId]);
            }

            $pdo->commit();
            setFlash('Teacher updated successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('Failed to update teacher: ' . $e->getMessage(), 'danger');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM teachers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        setFlash('Teacher deleted successfully.');
    }

    header('Location: teachers.php');
    exit;
}

$teachers = $pdo->query('SELECT * FROM teachers ORDER BY name ASC')->fetchAll();
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC')->fetchAll();

$qualificationRows = $pdo->query('SELECT teacher_id, subject_id FROM teacher_subjects')->fetchAll();
$qualificationMap = [];
foreach ($qualificationRows as $row) {
    $qualificationMap[(int) $row['teacher_id']][] = (int) $row['subject_id'];
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Teachers</h3>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">Add Teacher</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="create">
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Email"></div>
            <div class="col-md-2"><input name="phone" class="form-control" placeholder="Phone"></div>
            <div class="col-md-2">
                <select name="section" class="form-select" required>
                    <option value="" selected disabled>Select Section</option>
                    <?php foreach ($teacherSections as $teacherSection): ?>
                        <option value="<?= sanitize($teacherSection) ?>"><?= sanitize($teacherSection) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input name="max_periods_per_day" type="number" class="form-control" min="1" max="8" value="6" required></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add</button></div>
            <div class="col-12">
                <label class="form-label">Qualified Subjects</label>
                <select name="subject_ids[]" class="form-select" multiple>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= (int) $subject['id'] ?>"><?= sanitize($subject['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Section</th><th>Max/day</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?= sanitize($teacher['name']) ?></td>
                    <td><?= sanitize((string) $teacher['email']) ?></td>
                    <td><?= sanitize((string) $teacher['phone']) ?></td>
                    <td><?= sanitize((string) ($teacher['section'] ?? '')) ?></td>
                    <td><?= (int) $teacher['max_periods_per_day'] ?></td>
                    <td><?= (int) $teacher['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= (int) $teacher['id'] ?>">Edit</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $teacher['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger confirm-delete" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr class="collapse" id="edit-<?= (int) $teacher['id'] ?>">
                    <td colspan="7">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $teacher['id'] ?>">
                            <div class="col-md-3"><input class="form-control" name="name" value="<?= sanitize($teacher['name']) ?>" required></div>
                            <div class="col-md-2"><input class="form-control" name="email" value="<?= sanitize((string) $teacher['email']) ?>"></div>
                            <div class="col-md-2"><input class="form-control" name="phone" value="<?= sanitize((string) $teacher['phone']) ?>"></div>
                            <div class="col-md-2">
                                <select class="form-select" name="section" required>
                                    <?php foreach ($teacherSections as $teacherSection): ?>
                                        <option value="<?= sanitize($teacherSection) ?>" <?= (($teacher['section'] ?? '') === $teacherSection) ? 'selected' : '' ?>>
                                            <?= sanitize($teacherSection) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1"><input class="form-control" type="number" min="1" max="8" name="max_periods_per_day" value="<?= (int) $teacher['max_periods_per_day'] ?>"></div>
                            <div class="col-md-2">
                                <select class="form-select" name="subject_ids[]" multiple>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?= (int) $subject['id'] ?>" <?= in_array((int) $subject['id'], $qualificationMap[(int) $teacher['id']] ?? [], true) ? 'selected' : '' ?>>
                                            <?= sanitize($subject['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 form-check pt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" <?= (int) $teacher['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Active</label>
                            </div>
                            <div class="col-md-1 d-grid"><button class="btn btn-success btn-sm" type="submit">Save</button></div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
