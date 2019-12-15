<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Config\Config;
use Johncms\Api\ToolsInterface;
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Config $config
 * @var ToolsInterface $tools
 * @var User $user
 * @var Render $view
 */

$config = di(Config::class);
$tools = di(Johncms\Api\ToolsInterface::class);
$user = di(User::class);
$view = di(Render::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('help', __DIR__ . '/templates/');

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    $_SESSION['ref'] = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config['homeurl'];
}

// Сколько смайлов разрешено выбрать пользователям?
$user_smileys = 20;

// Названия директорий со смайлами
function smiliesCat()
{
    return [
        'animals'       => _t('Animals'),
        'brawl_weapons' => _t('Brawl, Weapons'),
        'emotions'      => _t('Emotions'),
        'flowers'       => _t('Flowers'),
        'food_alcohol'  => _t('Food, Alcohol'),
        'gestures'      => _t('Gestures'),
        'holidays'      => _t('Holidays'),
        'love'          => _t('Love'),
        'misc'          => _t('Miscellaneous'),
        'music'         => _t('Music, Dancing'),
        'sports'        => _t('Sports'),
        'technology'    => _t('Technology'),
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
    ob_start(); // Перехват вывода скриптов без шаблона
    require __DIR__ . '/includes/' . $array[$key] . '.php';
    echo $view->render(
        'system::app/old_content',
        [
            'title'   => $textl ?? _t('Information, FAQ'),
            'content' => ob_get_clean(),
        ]
    );
} else {
    // Главное меню FAQ
    echo $view->render('help::index');
}
