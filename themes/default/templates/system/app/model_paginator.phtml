<?php
/**
 * Шаблон постраничной навигации используемый в моделях
 *
 * @var $elements
 * @var \Illuminate\Pagination\Paginator $paginator
 */

?>
<?php if ($paginator->hasPages()): ?>
    <nav>
        <ul class="pagination">
            <!-- Previous Page Link -->
            <?php if ($paginator->onFirstPage()): ?>
                <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            <?php else: ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $paginator->previousPageUrl() ?>" rel="prev" aria-label="Previous page">&laquo;</a>
                </li>
            <?php endif ?>

            <!-- Pagination Elements -->
            <?php foreach ($elements as $element): ?>
                <!-- "Three Dots" Separator -->
                <?php if (is_string($element)): ?>
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link"><?= $element ?></span></li>
                <?php endif ?>

                <!-- Array Of Links -->
                <?php if (is_array($element)): ?>
                    <?php foreach ($element as $page => $url): ?>
                        <?php if ($page === $paginator->currentPage()): ?>
                            <li class="page-item active" aria-current="page"><span class="page-link"><?= $page ?></span></li>
                        <?php else: ?>
                            <li class="page-item"><a class="page-link" href="<?= $url ?>"><?= $page ?></a></li>
                        <?php endif ?>
                    <?php endforeach ?>
                <?php endif ?>
            <?php endforeach ?>

            <!-- Next Page Link -->
            <?php if ($paginator->hasMorePages()): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $paginator->nextPageUrl() ?>" rel="next" aria-label="Next">&raquo;</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            <?php endif ?>
        </ul>
    </nav>
<?php endif; ?>
