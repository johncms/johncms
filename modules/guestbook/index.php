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
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var User $user */
$user = di(User::class);

/** @var Render $view */
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

$config = di('config')['johncms'];
$route = di('route');

// Register Namespace for module templates
$view->addFolder('guestbook', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('guestbook', __DIR__ . '/locale');

$loader = new Loader();
$loader->register();
$loader->addPrefix('Guestbook', __DIR__ . '/lib');

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
$act = $route['action'] ?? '';

// Here you can (separated by commas) add the ID of those users who are not in the administration.
// But who are allowed to read and write in the admin club
$guestAccess = [];

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Check the access rights to the Admin Club
if (isset($_SESSION['ga']) && $user->rights < 1 && ! in_array($user->id, $guestAccess)) {
    unset($_SESSION['ga']);
}

// Set page headers
$title = isset($_SESSION['ga']) ? __('Admin Club') : __('Guestbook');

$nav_chain->add($title);

// If the guest is closed, display a message and close access (except for Admins)
if (! $config['mod_guest'] && $user->rights < 7) {
    echo $view->render(
        'guestbook::result',
        [
            'title'    => $title,
            'message'  => __('Guestbook is closed'),
            'type'     => 'error',
            'back_url' => '/',
        ]
    );
    exit;
}

switch ($act) {
    case 'delpost':
        require 'includes/delete.php';
        break;

    case 'otvet':
        require 'includes/reply.php';
        break;

    case 'edit':
        require 'includes/edit.php';
        break;

    case 'ga':
        require 'includes/switch.php';
        break;

    case 'clean':
        require 'includes/clean.php';
        break;

    default:
        require 'includes/index.php';
        break;
}
