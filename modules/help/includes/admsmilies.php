<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$title = __('For administration');
$nav_chain->add(__('Smilies'), '?act=smilies');
$nav_chain->add($title);

// Каталог Админских Смайлов
if ($user->rights < 1) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

$user_sm = unserialize($user->smileys, ['allowed_classes' => false]);

if (! is_array($user_sm)) {
    $user_sm = [];
}
$data = [];
$data['user_smiles_current'] = count($user_sm);
$data['user_smiles_max'] = $user_smileys;

$array = [];
$dir = opendir(ASSETS_PATH . 'emoticons/admin');

while (($file = readdir($dir)) !== false) {
    if (($file != '.') && ($file != '..') && ($file != 'name.dat') && ($file != '.svn') && ($file != 'index.php')) {
        $array[] = $file;
    }
}

closedir($dir);
$total = count($array);
if ($total > 0) {
    $end = $start + $user->config->kmess;

    if ($end > $total) {
        $end = $total;
    }

    $items = [];
    for ($i = $start; $i < $end; $i++) {
        $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', $array[$i], 1);
        $items[] = [
            'can_add'   => ($user->isValid() && ! in_array($smile, $user_sm)),
            'lat_smile' => $smile,
            'smile'     => $tools->trans($smile),
            'picture'   => '../assets/emoticons/admin/' . $array[$i],
        ];
    }
}

$data['pagination'] = $tools->displayPagination('?act=admsmilies&amp;', $start, $total, $user->config->kmess);
$data['form_action'] = '?act=set_my_sm&amp;start=' . $start . '&amp;adm';
$data['total'] = $total;
$data['items'] = $items ?? [];
$data['back_url'] = '?act=smilies';

echo $view->render(
    'help::smiles_list',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
