<?php

declare(strict_types=1);

function daysOfWeek(): array
{
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
}

function periods(): array
{
    return range(1, 8);
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getTeacherWorkload(PDO $pdo, ?int $teacherId = null): array
{
    $params = [];
    $where = '';
    if ($teacherId !== null) {
        $where = 'WHERE t.id = :teacher_id';
        $params['teacher_id'] = $teacherId;
    }

    $sql = "SELECT t.id, t.name,
                COUNT(tt.id) AS assigned_periods,
                COUNT(DISTINCT CONCAT(tt.day_of_week, '-', tt.period_number)) AS unique_slots
            FROM teachers t
            LEFT JOIN timetable tt ON tt.teacher_id = t.id
            $where
            GROUP BY t.id, t.name
            ORDER BY assigned_periods ASC, t.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function autoAssignRelief(PDO $pdo, int $absenceId): ?array
{
    $absenceStmt = $pdo->prepare(
        'SELECT a.*, tt.class_id, tt.subject_id, tt.period_number
         FROM absences a
         INNER JOIN timetable tt ON tt.teacher_id = a.teacher_id
            AND tt.day_of_week = a.day_of_week
            AND tt.period_number = a.period_number
         WHERE a.id = :absence_id'
    );
    $absenceStmt->execute(['absence_id' => $absenceId]);
    $absence = $absenceStmt->fetch();

    if (!$absence) {
        return null;
    }

    $candidateSql = 'SELECT t.id, t.name, t.max_periods_per_day,
                        COUNT(day_tt.id) AS day_load,
                        COUNT(all_tt.id) AS total_load
                     FROM teachers t
                     INNER JOIN teacher_subjects ts ON ts.teacher_id = t.id AND ts.subject_id = :subject_id
                     LEFT JOIN timetable slot_tt ON slot_tt.teacher_id = t.id
                        AND slot_tt.day_of_week = :day
                        AND slot_tt.period_number = :period
                     LEFT JOIN absences a2 ON a2.teacher_id = t.id
                        AND a2.absence_date = :absence_date
                        AND a2.day_of_week = :day
                        AND a2.period_number = :period
                     LEFT JOIN relief_assignments ra2 ON ra2.relief_teacher_id = t.id
                        AND ra2.relief_date = :absence_date
                        AND ra2.day_of_week = :day
                        AND ra2.period_number = :period
                     LEFT JOIN timetable day_tt ON day_tt.teacher_id = t.id
                        AND day_tt.day_of_week = :day
                     LEFT JOIN timetable all_tt ON all_tt.teacher_id = t.id
                     WHERE t.is_active = 1
                       AND t.id <> :absent_teacher_id
                       AND slot_tt.id IS NULL
                       AND a2.id IS NULL
                       AND ra2.id IS NULL
                     GROUP BY t.id, t.name, t.max_periods_per_day
                     HAVING day_load < t.max_periods_per_day
                     ORDER BY total_load ASC, day_load ASC, t.name ASC
                     LIMIT 1';

    $candidateStmt = $pdo->prepare($candidateSql);
    $candidateStmt->execute([
        'subject_id' => $absence['subject_id'],
        'day' => $absence['day_of_week'],
        'period' => $absence['period_number'],
        'absence_date' => $absence['absence_date'],
        'absent_teacher_id' => $absence['teacher_id'],
    ]);

    $candidate = $candidateStmt->fetch();
    if (!$candidate) {
        return null;
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO relief_assignments
        (absence_id, absent_teacher_id, relief_teacher_id, class_id, subject_id, relief_date, day_of_week, period_number, notes)
        VALUES
        (:absence_id, :absent_teacher_id, :relief_teacher_id, :class_id, :subject_id, :relief_date, :day_of_week, :period_number, :notes)'
    );

    $insertStmt->execute([
        'absence_id' => $absenceId,
        'absent_teacher_id' => $absence['teacher_id'],
        'relief_teacher_id' => $candidate['id'],
        'class_id' => $absence['class_id'],
        'subject_id' => $absence['subject_id'],
        'relief_date' => $absence['absence_date'],
        'day_of_week' => $absence['day_of_week'],
        'period_number' => $absence['period_number'],
        'notes' => 'Auto-assigned based on qualification and lowest workload',
    ]);

    return $candidate;
}
