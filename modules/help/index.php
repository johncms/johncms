<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 */

$config = di('config')['johncms'];
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('help', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('help', __DIR__ . '/locale');

$id = $request->getQuery('id', 0, FILTER_SANITIZE_NUMBER_INT);
$act = $request->getQuery('act', '', FILTER_SANITIZE_STRING);
$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    $_SESSION['ref'] = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config['homeurl'];
}

$title = __('Information, FAQ');
$nav_chain->add($title, '/help/');

// Сколько смайлов разрешено выбрать пользователям?
$user_smileys = 20;

// Названия директорий со смайлами
function smiliesCat()
{
    return [
        'animals'       => __('Animals'),
        'brawl_weapons' => __('Brawl, Weapons'),
        'emotions'      => __('Emotions'),
        'flowers'       => __('Flowers'),
        'food_alcohol'  => __('Food, Alcohol'),
        'gestures'      => __('Gestures'),
        'holidays'      => __('Holidays'),
        'love'          => __('Love'),
        'misc'          => __('Miscellaneous'),
        'music'         => __('Music, Dancing'),
        'sports'        => __('Sports'),
        'technology'    => __('Technology'),
    ];
}

// Выбор действия
$array = [
    'admsmilies',
    'avatars',
    'forum',
    'my_smilies',
    'set_my_sm',
    'smilies',
    'tags',
    'usersmilies',
];

if ($act && ($key = array_search($act, $array)) !== false && file_exists(__DIR__ . '/includes/' . $array[$key] . '.php')) {
    require __DIR__ . '/includes/' . $array[$key] . '.php';
} else {
    // Главное меню FAQ
    echo $view->render(
        'help::index',
        [
            'title'      => $title,
            'page_title' => $title,
        ]
    );
}
