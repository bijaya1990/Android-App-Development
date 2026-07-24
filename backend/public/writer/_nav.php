<?php
/** @var string $active */
?>
<header class="topbar">
    <div>
        <a href="/writer/article_form.php" class="<?= $active === 'new' ? 'active' : '' ?>">New Article</a>
        <a href="/writer/dashboard.php?tab=draft" class="<?= $active === 'draft' ? 'active' : '' ?>">Draft</a>
        <a href="/writer/dashboard.php?tab=sent" class="<?= $active === 'sent' ? 'active' : '' ?>">Sent</a>
        <a href="/writer/dashboard.php?tab=published" class="<?= $active === 'published' ? 'active' : '' ?>">Published</a>
        <a href="/writer/dashboard.php?tab=returned" class="<?= $active === 'returned' ? 'active' : '' ?>">Returned</a>
    </div>
    <form method="post" action="/logout.php" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit">Log Out (<?= e($_SESSION['full_name']) ?>)</button>
    </form>
</header>
