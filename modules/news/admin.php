<?php

declare(strict_types=1);

use News\Actions\Admin;
use News\Actions\Article;
use News\Actions\Section;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\User;

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var User $user */
$user = di(User::class);

$route = di('route');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('news', __DIR__ . '/locale');

module_lib_loader('news');

$category = $route['action'] ?? 'index';
$category = rtrim($category, '/');

$nav_chain->add(__('News'), '/news/');
$nav_chain->add(__('Admin panel'), '/news/admin/');

// Проверяем запрошенную страницу. Если это одна из админских страниц, пытаемся её открыть.
if ($user->rights >= 9) {
    switch ($category) {
        case 'del_section':
            (new Section())->del();
            break;
        case 'del_article':
            (new Article())->del();
            break;

        // Главная страница админки
        case 'index':
            (new Admin())->index();
            break;

        // Управление контентом
        case 'content':
            (new Admin())->section();
            break;

        // Настройки
        case 'settings':
            (new Admin())->settings();
            break;

        // Страница добавления раздела
        case 'add_section':
            (new Section())->add();
            break;

        // Страница редактирования раздела
        case 'edit_section':
            (new Section())->edit();
            break;

        // Страница создания статьи
        case 'add_article':
            (new Article())->add();
            break;

        // Страница изменения статьи
        case 'edit_article':
            (new Article())->edit();
            break;

        // Неизвестная страница
        default:
            pageNotFound();
    }
} else {
    pageNotFound();
}
