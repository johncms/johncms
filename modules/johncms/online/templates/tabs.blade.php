<?php

use Johncms\Online\Tabs;

$tabs = di(Tabs::class)->getTabs()
?>
<div class="mb-2">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>" class="btn btn-outline-secondary mb-1 <?= ($tab['active'] ? 'active' : '') ?>"><?= $tab['name'] ?></a>
    <?php endforeach; ?>
</div>
