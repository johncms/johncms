<?php
/**
 * @var string $locale
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\Users\User $user
 * @var Johncms\Ads $ads
 */

use Johncms\Utility\Numbers;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;

$ads = di(Johncms\Ads::class);
$ads_array = $ads->getAds();

?>
<!-- Меню -->
<div class="flex-grow-1">
    <?php if (! empty($ads_array['before_menu'])): ?>
        <div class="ps-4 pe-3 pt-3">
            <?php foreach ($ads_array['before_menu'] as $item): ?>
                <div><?= $item ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <ul class="nav nav__vertical">
        <!-- Menu -->
        <?php $menu = MenuFactory::create(Menu::PUBLIC_SIDEBAR)->getItems(); ?>
        <?php foreach ($menu as $item): ?>
            <li>
                <a href="<?= $item['url'] ?>" class="<?= $item['active'] ? 'opacity-100' : '' ?>">
                    <svg class="icon">
                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#<?= $item['icon'] ?>"/>
                    </svg>
                    <span class="flex-grow-1"><?= $item['name'] ?></span>
                    <?php if (is_numeric($item['counter']) && $item['counter'] > 0): ?>
                        <span class="badge rounded-pill bg-danger">+ <?= Numbers::formatNumber($item['counter']) ?></span>
                    <?php endif ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($user?->hasAnyRole()): ?>
            <!-- Admin Menu -->
            <?php $adminMenu = MenuFactory::create(Menu::PUBLIC_ADMIN)->getItems(); ?>
            <li>
                <div class="border-bottom mt-3 mb-3"></div>
            </li>
            <?php foreach ($adminMenu as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>" class="text-danger">
                        <svg class="icon">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#<?= $item['icon'] ?>"/>
                        </svg>
                        <span class="flex-grow-1"><?= $item['name'] ?></span>
                        <?php if (is_numeric($item['counter']) && $item['counter'] > 0): ?>
                            <span class="badge rounded-pill bg-danger">+ <?= Numbers::formatNumber($item['counter']) ?></span>
                        <?php endif ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif ?>
    </ul>
    <div class="border-bottom"></div>
</div>
<div class="sidebar__footer">
    <?php if (! empty($ads_array['after_menu'])): ?>
        <div class="pe-3 pt-2 mb-2">
            <?php foreach ($ads_array['after_menu'] as $item): ?>
                <div><?= $item ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div>
        <a href="<?= route('online.index') ?>">
            <svg class="icon sidebar_online">
                <use xlink:href="<?= asset('icons/sprite.svg') ?>#user"/>
            </svg>
            <?php $onlineCounter = di(\Johncms\Online\OnlineCounter::class); ?>
            <?= $onlineCounter->getUsers() . ' / ' . $onlineCounter->getGuests() ?>
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
