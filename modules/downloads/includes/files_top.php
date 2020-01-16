<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

// Топ файлов
if ($id === 2) {
    $title = __('Most Commented');
} elseif ($id === 1) {
    $title = __('Most Downloaded');
} else {
    $title = __('Popular Files');
}

$nav_chain->add($title);

$buttons = [];
if ($config['mod_down_comm'] || $user->rights >= 7) {
    $buttons['comments'] = [
        'name'   => __('Most Commented'),
        'url'    => '?act=top_files&amp;id=2',
        'active' => false,
    ];
}

$buttons['pop'] = [
    'name'   => __('Popular Files'),
    'url'    => '?act=top_files&amp;id=0',
    'active' => false,
];

$buttons['most_downloaded'] = [
    'name'   => __('Most Downloaded'),
    'url'    => '?act=top_files&amp;id=1',
    'active' => false,
];

if ($id === 2 && ($config['mod_down_comm'] || $user->rights >= 7)) {
    $buttons['comments']['active'] = true;
    $sql = '`comm_count`';
} elseif ($id === 1) {
    $buttons['most_downloaded']['active'] = true;
    $sql = '`field`';
} else {
    $buttons['pop']['active'] = true;
    $sql = '`rate`';
}

// Выводим список
$req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = 2 ORDER BY ${sql} DESC LIMIT " . $set_down['top']);
$files = [];
while ($res_down = $req_down->fetch()) {
    $files[] = Download::displayFile($res_down);
}

echo $view->render(
    'downloads::top',
    [
        'title'      => $title,
        'page_title' => $title,
        'files'      => $files ?? [],
        'urls'       => $urls,
        'buttons'    => $buttons,
    ]
);
