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
 * @var Johncms\System\Users\User $user
 */

$title = __('Smiles');
$nav_chain->add($title, '?act=smilies');

$items = [];
if ($user->isValid()) {
    $mycount = ! empty($user->smileys) ? count(unserialize($user->smileys, ['allowed_classes' => false])) : '0';
    $items[] = [
        'url'   => '?act=my_smilies',
        'name'  => __('My smilies'),
        'count' => $mycount . ' / ' . $user_smileys,
    ];
}

if ($user->rights >= 1) {
    $items[] = [
        'url'   => '?act=admsmilies',
        'name'  => __('For administration'),
        'count' => (int) count(glob(ASSETS_PATH . 'emoticons/admin/*.gif')),
    ];
}

$dir = glob(ASSETS_PATH . 'emoticons/user/*', GLOB_ONLYDIR);

foreach ($dir as $val) {
    $cat = strtolower(basename($val));

    if (array_key_exists($cat, smiliesCat())) {
        $smileys_cat[$cat] = smiliesCat()[$cat];
    } else {
        $smileys_cat[$cat] = ucfirst($cat);
    }
}

asort($smileys_cat);
$i = 0;

foreach ($smileys_cat as $key => $val) {
    $items[] = [
        'url'   => '?act=usersmilies&amp;cat=' . urlencode($key),
        'name'  => htmlspecialchars($val),
        'count' => count(glob(ASSETS_PATH . 'emoticons/user/' . $key . '/*.{gif,jpg,png}', GLOB_BRACE)),
    ];
}

$data['items'] = $items ?? [];
$data['back_url'] = htmlspecialchars($_SESSION['ref']);

echo $view->render(
    'help::smiles',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
