<div class="sidebar">
    <div class="user-profile">
        <div class="profile-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="profile-info">
            <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
            <p>Student</p>
        </div>
    </div>

    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="browse_courses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'browse_courses.php' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Browse Courses
        </a>
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="assessmentDropdown" role="button" 
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-file-alt"></i> Assessments
            </a>
            <div class="dropdown-menu" aria-labelledby="assessmentDropdown">
                <a class="dropdown-item" href="assessments.php?type=quiz">
                    <i class="fas fa-question-circle mr-2"></i>Quizzes
                </a>
                <a class="dropdown-item" href="assessments.php?type=midterm">
                    <i class="fas fa-file-alt mr-2"></i>Midterms
                </a>
                <a class="dropdown-item" href="assessments.php?type=final">
                    <i class="fas fa-graduation-cap mr-2"></i>Finals
                </a>
            </div>
        </div>
        <a href="view_grades.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_grades.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> My Grades
        </a>
        <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> Profile
        </a>
        <a href="../logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div> 