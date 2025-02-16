<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireTeacher();

$submission_id = $_GET['id'] ?? 0;

// Fetch submission details with student and assignment info
$stmt = $pdo->prepare("
    SELECT s.*, 
           u.full_name as student_name,
           u.email as student_email,
           a.title as assignment_title,
           a.max_points,
           c.course_name,
           c.teacher_id
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND c.teacher_id = ?
");
$stmt->execute([$submission_id, $_SESSION['user_id']]);
$submission = $stmt->fetch();

if (!$submission) {
    echo "Submission not found or access denied.";
    exit();
}
?>

<div class="submission-details">
    <!-- Student Info -->
    <div class="mb-4">
        <h6 class="text-muted">Student Information</h6>
        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($submission['student_name']); ?></p>
        <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($submission['student_email']); ?></p>
    </div>

    <!-- Submission Content -->
    <div class="mb-4">
        <h6 class="text-muted">Submission Content</h6>
        <div class="submission-text p-3 bg-light rounded">
            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
        </div>
    </div>

    <!-- Attached File -->
    <?php if ($submission['file_path']): ?>
        <div class="mb-4">
            <h6 class="text-muted">Attached File</h6>
            <div class="d-flex align-items-center">
                <i class="fas fa-paperclip mr-2"></i>
                <a href="../download.php?type=submission&id=<?php echo $submission['id']; ?>" 
                   class="btn btn-sm btn-outline-primary">
                    Download Attachment
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submission Details -->
    <div class="mb-4">
        <h6 class="text-muted">Submission Details</h6>
        <p class="mb-1">
            <strong>Submitted:</strong> 
            <?php echo date('M d, Y g:i A', strtotime($submission['submitted_at'])); ?>
        </p>
        <p class="mb-1">
            <strong>Status:</strong>
            <?php if ($submission['grade'] !== null): ?>
                <span class="badge badge-success">Graded</span>
            <?php else: ?>
                <span class="badge badge-warning">Pending</span>
            <?php endif; ?>
        </p>
        <?php if ($submission['grade'] !== null): ?>
            <p class="mb-1">
                <strong>Grade:</strong>
                <span class="badge badge-info">
                    <?php echo $submission['grade']; ?>/<?php echo $submission['max_points']; ?>
                </span>
            </p>
            <?php if ($submission['feedback']): ?>
                <div class="mt-3">
                    <strong>Feedback:</strong>
                    <div class="feedback-text p-3 bg-light rounded mt-2">
                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.submission-details {
    padding: 1rem;
}

.submission-text {
    white-space: pre-wrap;
    font-family: 'Roboto', sans-serif;
    border: 1px solid #dee2e6;
}

.feedback-text {
    white-space: pre-wrap;
    font-family: 'Roboto', sans-serif;
    border: 1px solid #dee2e6;
}

h6.text-muted {
    font-weight: 600;
    margin-bottom: 1rem;
}
</style> 