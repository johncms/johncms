<?php
/**
 * @var int $bantotal
 * @var int $countadm
 * @var int $countusers
 * @var string $locale
 * @var int $regtotal
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\Users\User $user
 */

/** @var \Johncms\Counters $counters */

use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;

$counters = $container->get('counters');
$forum_counters = $counters->forumCounters();
$guestbook_counters = $counters->guestbookCounters();
$downloads_counters = $counters->downloadsCounters();
$library_counters = $counters->libraryCounters();
$users_counters = $counters->usersCounters();

$menu = MenuFactory::create(Menu::ADMIN_SIDEBAR)->getItems();

?>
<div style="background-color: #bd0719; height: 5px">&nbsp;</div>

<div class="accordion flex-grow-1" id="accordionAdmin">

    <?php foreach ($menu as $key => $item): ?>
        <?php if (empty($item['children'])): ?>
            <!-- Without children items -->
            <a class="nav-link user__link border-bottom" href="<?= $item['url'] ?>">
                <div class="nav__vertical pt-2 pb-2 text-danger d-flex align-items-center">
                    <svg class="icon">
                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#<?= $item['icon'] ?>"/>
                    </svg>
                    <span class="flex-grow-1"><?= $item['name'] ?></span>
                    <?php if ($item['counter']): ?>
                        <div class="badge bg-danger rounded-pill ms-2"><?= $item['counter'] ?></div>
                    <?php endif ?>
                </div>
            </a>
        <?php else: ?>
            <!-- With children items -->
            <a class="nav-link user__link border-bottom" href="#" data-bs-toggle="collapse" data-bs-target="#menu_<?= $key ?>" aria-expanded="false">
                <div class="nav__vertical pt-2 pb-2 text-danger d-flex align-items-center">
                    <svg class="icon">
                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#<?= $item['icon'] ?>"/>
                    </svg>
                    <span class="flex-grow-1"><?= $item['name'] ?></span>
                    <?php if ($item['counter']): ?>
                        <div class="badge bg-danger rounded-pill ms-2"><?= $item['counter'] ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <svg class="icon icon-chevron-bottom">
                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#chevron-bottom"/>
                    </svg>
                </div>
            </a>

            <div id="menu_<?= $key ?>" class="border-bottom collapse<?= $item['active'] ? ' show' : '' ?>" data-parent="#accordionAdmin">
                <ul class="nav nav__vertical">
                    <?php foreach ($item['children'] as $child): ?>
                        <li class="<?= $child['active'] ? 'active' : '' ?>">
                            <a href="<?= $child['url'] ?>" class="text-decoration-none">
                                <svg class="icon">
                                    <use xlink:href="<?= asset('icons/sprite.svg') ?>#<?= $child['icon'] ?>"/>
                                </svg>
                                <span class="flex-grow-1"><?= $child['name'] ?></span>
                                <?php if ($child['counter']): ?>
                                    <span class="badge rounded-pill badge-light"><?= $child['counter'] ?></span>
                                <?php endif ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- Footer -->
<div class="sidebar__footer">
    <div>
        <a href="/online/">
            <svg class="icon sidebar_online">
                <use xlink:href="<?= asset('icons/sprite.svg') ?>#user"/>
            </svg>
            <?= $container->get('counters')->online() ?>
        </a>
    </div>

    <?php if (count($config['lng_list']) > 1): ?>
        <div class="mt-1">
            <button class="btn btn-link ps-0" data-url="/language/" data-bs-toggle="modal" data-bs-target=".ajax_modal">
                <img class="icon icon-flag" src="<?= asset('images/flags/' . strtolower($locale) . '.svg') ?>" alt="<?= $locale ?>">
                <?= $config['lng_list'][$locale]['name'] ?>
            </button>
        </div>
    <?php endif ?>
</div>
