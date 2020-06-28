<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Admin\Languages\Languages;
use Johncms\NavChain;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var NavChain $nav_chain
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$config = di('config')['johncms'];

$view->addData(
    [
        'title'      => __('Managing languages'),
        'page_title' => __('Managing languages'),
    ]
);

$nav_chain->add(__('Managing languages'), '/admin/languages/?action=manage');

$all_languages = Languages::getAvailableLanguages();
$languages = [];
foreach ($config['lng_list'] as $key => $item) {
    $item['installed'] = true;

    $item['need_update'] = false;
    $item['new_version'] = '';
    if (array_key_exists($key, $all_languages)) {
        $update = $all_languages[$key];
        if ($update['version'] > $item['version']) {
            $item['need_update'] = true;
            $item['new_version'] = $update['version'];
        }
    }

    $flag = THEMES_PATH . 'default/assets/images/flags/' . strtolower($key) . '.png';
    if (is_file($flag)) {
        $item['flag'] = pathToUrl($flag);
    }

    $languages[$key] = $item;
}

$languages = array_merge($all_languages, $languages);

unset($languages['en']);

$message = null;
if (! empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

echo $view->render(
    'admin::languages/manage',
    [
        'message'      => $message,
        'languages'    => $languages,
        'confirmation' => $confirmation ?? false,
    ]
);
