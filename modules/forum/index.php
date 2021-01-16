<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Aura\Autoload\Loader;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\Counters;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var Counters $counters
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 */
$assets = di(Assets::class);
$config = di('config')['johncms'];
$counters = di('counters');
$db = di(PDO::class);
$user = di(User::class);
$tools = di(Tools::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('forum', __DIR__ . '/locale');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('forum', __DIR__ . '/templates/');

// Регистрируем автозагрузчик
$loader = new Loader();
$loader->register();
$loader->addPrefix('Forum', __DIR__ . '/lib');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(__('Forum'), '/forum/');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Настройки форума
$set_forum_default = [
    'farea'    => 0,
    'upfp'     => 0,
    'preview'  => 1,
    'postclip' => 1,
    'postcut'  => 2,
];
$set_forum = [];
if ($user->isValid() && ! empty($user->set_forum)) {
    $set_forum = unserialize($user->set_forum, ['allowed_classes' => false]);
}
$set_forum = array_merge($set_forum_default, (array) $set_forum);

$user_rights_names = [
    3 => __('Forum moderator'),
    4 => __('Download moderator'),
    5 => __('Library moderator'),
    6 => __('Super moderator'),
    7 => __('Administrator'),
    9 => __('Supervisor'),
];

// Ограничиваем доступ к Форуму
$error = '';

if (! $config['mod_forum'] && $user->rights < 7) {
    $error = __('Forum is closed');
} elseif ($config['mod_forum'] === 1 && ! $user->isValid()) {
    $error = __('For registered users only');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('Forum'),
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
    exit;
}
$show_type = $_REQUEST['type'] ?? 'section';

// Переключаем режимы работы
$mods = [
    'addfile',
    'addvote',
    'close',
    'deltema',
    'delvote',
    'editpost',
    'editvote',
    'file',
    'files',
    'filter',
    'loadtem',
    'massdel',
    'new',
    'nt',
    'per',
    'show_post',
    'change_topic',
    'restore',
    'say',
    'search',
    'tema',
    'users',
    'vip',
    'vote',
    'who',
    'curators',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} elseif ($id) {
    switch ($show_type) {
        case 'section':
            require 'includes/section.php';
            break;

        case 'topics':
            require 'includes/topics.php';
            break;

        case 'topic':
            require 'includes/topic.php';
            break;

        default:
            pageNotFound();
            break;
    }
} else {
    require 'includes/index.php';
}
