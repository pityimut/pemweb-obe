<?php
/**
 * Top Navbar Header Component
 * Warung Makan Hanisa
 */
?>
<header class="app-header">
    <div class="header-left">
        <button class="toggle-sidebar-btn" id="sidebarToggle" aria-label="Toggle Navigation">
            ☰
        </button>
        <div class="page-title-wrap">
            <h1><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
            <p><?= htmlspecialchars($page_subtitle ?? 'Warung Makan Hanisa POS System') ?></p>
        </div>
    </div>

    <div class="header-right">
        <div class="current-date-badge">
            <span>📅</span>
            <span><?= date('l, d F Y') ?></span>
        </div>

        <?php if (($current_page ?? '') !== 'kasir'): ?>
            <a href="<?= BASE_URL ?>kasir/index.php" class="btn btn-primary btn-sm">
                <span>🛒 Buka Kasir</span>
            </a>
        <?php endif; ?>
    </div>
</header>
