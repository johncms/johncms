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
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\System\i18n\Translator;
use Aura\Autoload\Loader;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 * @var Request $request
 */

$assets = di(Assets::class);
$config = di('config')['johncms'];
$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);
$request = di(Request::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('library', __DIR__ . '/locale');

// Регистрируем автозагрузчик для классов библиотеки
$loader = new Loader();
$loader->register();
$loader->addPrefix('Library', __DIR__ . '/classes');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('library', __DIR__ . '/templates/');
$view->addFolder('libraryHelpers', __DIR__ . '/templates/helpers/');

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
$act = $request->getQuery('act', '', FILTER_SANITIZE_STRING);
$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

$adm = ($user->rights > 4);

$title = __('Library');
$nav_chain->add($title, '/library/');

$error = '';
// Ограничиваем доступ к Библиотеке
if (! $config['mod_lib'] && $user->rights < 7) {
    $error = __('Library is closed');
} elseif ($config['mod_lib'] === 1 && ! $user->isValid()) {
    $error = __('Access forbidden');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
    exit;
}

// Динамические заголовки библиотеки
$tab = $do === 'dir' ? 'library_cats' : 'library_texts';

if ($id > 0) {
    $hdrsql = $db->query('SELECT `name` FROM `' . $tab . '` WHERE `id` = ' . $id . ' LIMIT 1');

    $hdrres = '';
    if ($hdrsql->rowCount()) {
        $hdrres = $hdrsql->fetchColumn();
    }

    $hdr = htmlentities($hdrres, ENT_QUOTES, 'UTF-8');
    if ($hdr) {
        $page_title = $hdr;
        $title .= ' | ' . (mb_strlen($hdr) > 30 ? $hdr . '...' : $hdr);
    }
}

$array_includes = [
    'addnew',
    'comments',
    'del',
    'download',
    'mkdir',
    'moder',
    'move',
    'new',
    'premod',
    'search',
    'top',
    'tags',
    'tagcloud',
    'lastcom',
];

if (! in_array($act, $array_includes, true)) {
    $act = 'index';
}
require_once 'includes/' . $act . '.php';
