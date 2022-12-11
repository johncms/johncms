<?php
/**
 * @var $items
 */
?>
<?php if (! empty($items)): ?>
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <?php foreach ($items as $item): ?>
            <li class="page-item <?= ! empty($item['active']) ? 'active' : '' ?>">
                <?php if (!empty($item['url'])): ?>
                    <a class="page-link" href="<?= $item['url'] ?>"><?= $item['name'] ?></a>
                <?php else: ?>
                    <span class="page-link"><?= $item['name'] ?></span>
                <?php endif ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>

