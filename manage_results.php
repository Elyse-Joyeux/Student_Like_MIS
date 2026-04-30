<?php

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_result'])) {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $marks = filter_var($_POST['marks'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        $exam_type = trim($_POST['exam_type'] ?? '');
        $term = date('n') <= 4 ? 1 : (date('n') <= 8 ? 2 : 3);
        $year = date('Y');

        $valid_student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE id=$student_id AND role='student'"));
        $valid_exam_types = ['Mid-Term', 'Final', 'Quiz'];

        if (!$valid_student || $subject === '' || $marks === false || !in_array($exam_type, $valid_exam_types, true)) {
            redirect('admin_dashboard.php?error=Invalid+result+details&section=results');
        }

        $grade = '';
        if ($marks >= 80) $grade = 'A';
        elseif ($marks >= 70) $grade = 'B';
        elseif ($marks >= 60) $grade = 'C';
        else $grade = 'D';

        $stmt = mysqli_prepare($conn, "INSERT INTO results (student_id, subject, exam_type, marks, grade, term, year) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issisii", $student_id, $subject, $exam_type, $marks, $grade, $term, $year);
        mysqli_stmt_execute($stmt);
        logAction($_SESSION['user_id'], 'Add Result', "Added result for student ID: $student_id");
    }

    if (isset($_POST['delete_result'])) {
        $result_id = (int)($_POST['result_id'] ?? 0);
        if ($result_id <= 0) {
            redirect('admin_dashboard.php?error=Invalid+result+selected&section=results');
        }
        mysqli_query($conn, "DELETE FROM results WHERE id=$result_id");
        logAction($_SESSION['user_id'], 'Delete Result', "Deleted result ID: $result_id");
    }
}

redirect('admin_dashboard.php?msg=Results+updated&section=results');
