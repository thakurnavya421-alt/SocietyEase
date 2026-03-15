<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('se_render_shell_start')) {
    function se_render_shell_start($title, $active, $dashboard_link, $is_admin) {
        $role_label = $is_admin ? 'Secretary' : 'Resident';
        $links = [
            ['href' => $dashboard_link, 'label' => 'Dashboard'],
            ['href' => 'residents.php', 'label' => $is_admin ? 'Residents' : 'Members'],
            ['href' => 'notices.php', 'label' => 'Notices'],
            ['href' => 'viewbills.php', 'label' => 'Bills'],
            ['href' => 'services.php', 'label' => 'Services'],
            ['href' => 'helpdesk.php', 'label' => 'Helpdesk'],
            ['href' => 'visitors.php', 'label' => 'Visitors'],
            ['href' => 'staff.php', 'label' => 'Staff'],
            ['href' => 'chat.php', 'label' => 'Chat'],
            ['href' => 'profile.php', 'label' => 'Profile'],
            ['href' => 'emergency.php', 'label' => 'Emergency'],
        ];
        $shell_role_class = $is_admin ? 'role-admin' : 'role-resident';
        ?>
        <div class="app-shell <?php echo $shell_role_class; ?>">
            <aside class="app-sidebar">
                <div class="app-brand">SocietyEase</div>
                <div class="app-role"><?php echo htmlspecialchars($role_label); ?> Panel</div>
                <nav class="app-nav">
                    <?php foreach ($links as $link): ?>
                        <a class="app-link <?php echo $active === $link['href'] ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($link['href']); ?>">
                            <?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <main class="app-main">
                <header class="app-topbar">
                    <h1 class="app-title"><?php echo htmlspecialchars($title); ?></h1>
                    <div class="app-top-actions">
                        <a class="btn app-ghost-btn" href="<?php echo htmlspecialchars($dashboard_link); ?>">Dashboard</a>
                        <a class="btn" href="logout.php">Logout</a>
                    </div>
                </header>
                <section class="app-content">
        <?php
    }
}

if (!function_exists('se_render_shell_end')) {
    function se_render_shell_end() {
        ?>
                </section>
            </main>
        </div>
        <?php
    }
}
