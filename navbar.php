<nav class="navbar">
    <div class="container">
        <a href="index.php" class="navbar-brand">NIST EVENT</a>
        <ul class="navbar-menu">
            <li><a href="index.php">Volunteer Portal</a></li>
            <li><a href="namelist/index.php">Names</a></li>
            <li><a href="nist_judging/index.php">Judging</a></li>
        </ul>
        <?php if (!isset($_SESSION['admin_id'])): ?>
            <a href="nist_admin/admin_login.php" class="btn btn-primary" style="padding:10px 20px;">Admin Login</a>
        <?php endif; ?>
    </div>
</nav>