<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="container">
        <a href="c_debug_admin.php" class="navbar-brand">NIST ADMIN</a>
        <ul class="navbar-menu">
            <li><a href="c_debug_admin.php" class="<?php echo $current_page == 'c_debug_admin.php' ? 'active' : ''; ?>">C-Debug</a></li>
            <li><a href="ui_ux_admin.php" class="<?php echo $current_page == 'ui_ux_admin.php' ? 'active' : ''; ?>">UI-UX</a></li>
            <li><a href="c_debug_scoring.php" class="<?php echo $current_page == 'c_debug_scoring.php' ? 'active' : ''; ?>">Scoring</a></li>
            <li><a href="volunteer_requests.php" class="<?php echo $current_page == 'volunteer_requests.php' ? 'active' : ''; ?>">Volunteers</a></li>
            <li><a href="manage_volunteers.php" class="<?php echo $current_page == 'manage_volunteers.php' ? 'active' : ''; ?>">Reg Volunteer</a></li>
            <li><a href="../nist_judging/index.php">Judging</a></li>
        </ul>
        <?php if (isset($_SESSION['admin_user'])): ?>
            <div style="display:flex; align-items:center; gap:20px;">
                <span style="font-size:0.8rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; color:var(--white);">
                    <?php echo htmlspecialchars($_SESSION['admin_user']); ?>
                </span>
                <a href="logout.php" class="btn btn-danger" style="padding:6px 12px; font-size:0.75rem;">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>