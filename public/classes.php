<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO classes (class_name, section) VALUES (:class_name, :section)');
        $stmt->execute([
            'class_name' => trim($_POST['class_name'] ?? ''),
            'section' => trim($_POST['section'] ?? '') ?: null,
        ]);
        setFlash('Class added successfully.');
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE classes SET class_name = :class_name, section = :section WHERE id = :id');
        $stmt->execute([
            'id' => (int) ($_POST['id'] ?? 0),
            'class_name' => trim($_POST['class_name'] ?? ''),
            'section' => trim($_POST['section'] ?? '') ?: null,
        ]);
        setFlash('Class updated successfully.');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = :id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('Class deleted successfully.');
    }

    header('Location: classes.php');
    exit;
}

$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3">Classes</h3>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-5"><input name="class_name" class="form-control" placeholder="Class name" required></div>
            <div class="col-md-5"><input name="section" class="form-control" placeholder="Section"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add Class</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Class</th><th>Section</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= sanitize($class['class_name']) ?></td>
                        <td><?= sanitize((string) $class['section']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= (int) $class['id'] ?>">Edit</button>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-<?= (int) $class['id'] ?>">
                        <td colspan="3">
                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <div class="col-md-5"><input name="class_name" class="form-control" value="<?= sanitize($class['class_name']) ?>" required></div>
                                <div class="col-md-5"><input name="section" class="form-control" value="<?= sanitize((string) $class['section']) ?>"></div>
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
