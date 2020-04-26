<?php
/**
 * @var $breadcrumbs
 */

use Johncms\NavChain;

$nav_chain = $container->get(NavChain::class);
$breadcrumbs = $nav_chain->getAll();
$last_item = array_key_last($breadcrumbs);
?>
<?php if (! empty($breadcrumbs)): ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" vocab="https://schema.org/" typeof="BreadcrumbList">
            <?php foreach ($breadcrumbs as $key => $breadcrumb): ?>
                <li class="breadcrumb-item <?= ! empty($breadcrumb['active']) ? 'active' : '' ?>" property="itemListElement" typeof="ListItem">
                    <?php if (! empty($breadcrumb['url'])): ?>
                        <a <?php if ($last_item !== $key): ?>property="item" typeof="WebPage"<?php endif; ?> href="<?= $breadcrumb['url'] ?>" title="<?= $breadcrumb['name'] ?>">
                            <span<?php if ($last_item !== $key): ?> property="name"<?php endif; ?>><?= $breadcrumb['name'] ?></span>
                        </a>
                        <?php if ($last_item === $key): ?><meta property="name" content="<?= $breadcrumb['name'] ?>"><?php endif; ?>
                    <?php else: ?>
                        <span property="name"><?= $breadcrumb['name'] ?></span>
                    <?php endif; ?>
                    <meta property="position" content="<?= ($key + 1) ?>">
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>

