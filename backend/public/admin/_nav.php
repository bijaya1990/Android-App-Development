<?php
/** @var string $active */
?>
<header class="topbar">
    <div>
        <a href="/admin/dashboard.php?tab=pending_review" class="<?= $active === 'pending_review' ? 'active' : '' ?>">Pending Review</a>
        <a href="/admin/dashboard.php?tab=draft" class="<?= $active === 'draft' ? 'active' : '' ?>">Draft</a>
        <a href="/admin/dashboard.php?tab=published" class="<?= $active === 'published' ? 'active' : '' ?>">Published</a>
        <a href="/admin/dashboard.php?tab=returned" class="<?= $active === 'returned' ? 'active' : '' ?>">Returned</a>
        <a href="/admin/writers.php" class="<?= $active === 'writers' ? 'active' : '' ?>">Content Writers</a>
    </div>
    <form method="post" action="/logout.php" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit">Log Out (<?= e($_SESSION['full_name']) ?>)</button>
    </form>
</header>
