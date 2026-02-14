<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO subjects (subject_name) VALUES (:subject_name)');
        $stmt->execute(['subject_name' => trim($_POST['subject_name'] ?? '')]);
        setFlash('Subject added successfully.');
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE subjects SET subject_name = :subject_name WHERE id = :id');
        $stmt->execute([
            'id' => (int) ($_POST['id'] ?? 0),
            'subject_name' => trim($_POST['subject_name'] ?? ''),
        ]);
        setFlash('Subject updated successfully.');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('Subject deleted successfully.');
    }

    header('Location: subjects.php');
    exit;
}

$subjects = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3">Subjects</h3>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-10"><input name="subject_name" class="form-control" placeholder="Subject name" required></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add Subject</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Subject</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= sanitize($subject['subject_name']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= (int) $subject['id'] ?>">Edit</button>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $subject['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-<?= (int) $subject['id'] ?>">
                        <td colspan="2">
                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= (int) $subject['id'] ?>">
                                <div class="col-md-10"><input name="subject_name" class="form-control" value="<?= sanitize($subject['subject_name']) ?>" required></div>
                                <div class="col-md-2 d-grid"><button class="btn btn-success btn-sm">Save</button></div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
