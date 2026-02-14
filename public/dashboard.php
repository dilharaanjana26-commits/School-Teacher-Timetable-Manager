<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$teacherCount = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$classCount = (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$subjectCount = (int) $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
$today = date('Y-m-d');
$todayReliefCountStmt = $pdo->prepare('SELECT COUNT(*) FROM relief_assignments WHERE relief_date = :today');
$todayReliefCountStmt->execute(['today' => $today]);
$todayReliefCount = (int) $todayReliefCountStmt->fetchColumn();

$workload = getTeacherWorkload($pdo);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Admin Dashboard</h3>
    <span class="text-muted">Welcome, <?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm"><div class="card-body"><h6>Teachers</h6><h3><?= $teacherCount ?></h3></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm"><div class="card-body"><h6>Classes</h6><h3><?= $classCount ?></h3></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm"><div class="card-body"><h6>Subjects</h6><h3><?= $subjectCount ?></h3></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm"><div class="card-body"><h6>Today's Relief</h6><h3><?= $todayReliefCount ?></h3></div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Teacher Workload Summary</div>
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Teacher</th><th>Total Assigned Periods</th><th>Unique Slots</th></tr></thead>
            <tbody>
            <?php foreach ($workload as $row): ?>
                <tr>
                    <td><?= sanitize($row['name']) ?></td>
                    <td><?= (int) $row['assigned_periods'] ?></td>
                    <td><?= (int) $row['unique_slots'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
