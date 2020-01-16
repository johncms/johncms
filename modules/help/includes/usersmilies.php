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

// Каталог пользовательских Смайлов
$dir = glob(ASSETS_PATH . 'emoticons/user/*', GLOB_ONLYDIR);
$cat_list = [];
foreach ($dir as $val) {
    $val = explode('/', $val);
    $cat_list[] = array_pop($val);
}

$cat = isset($_GET['cat']) && in_array(trim($_GET['cat']), $cat_list) ? trim($_GET['cat']) : $cat_list[0];
$smileys = glob(ASSETS_PATH . 'emoticons/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE);
$total = count($smileys);
$end = $start + $user->config->kmess;

if ($end > $total) {
    $end = $total;
}

$title = (array_key_exists($cat, smiliesCat()) ? smiliesCat()[$cat] : ucfirst(htmlspecialchars($cat)));

$nav_chain->add(__('Smilies'), '?act=smilies');
$nav_chain->add($title);

if ($total) {
    if ($user->isValid()) {
        $user_sm = isset($user->smileys) ? unserialize($user->smileys, ['allowed_classes' => false]) : '';
        if (! is_array($user_sm)) {
            $user_sm = [];
        }
        $data['user_smiles_current'] = count($user_sm);
        $data['user_smiles_max'] = $user_smileys;
    }
    $items = [];
    for ($i = $start; $i < $end; $i++) {
        $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', basename($smileys[$i]));
        $items[] = [
            'can_add'   => ($user->isValid() && ! in_array($smile, $user_sm)),
            'lat_smile' => $smile,
            'smile'     => $tools->trans($smile),
            'picture'   => '../assets/emoticons/user/' . $cat . '/' . basename($smileys[$i]),
        ];
    }
}

$data['pagination'] = $tools->displayPagination('?act=usersmilies&amp;cat=' . urlencode($cat) . '&amp;', $start, $total, $user->config->kmess);
$data['form_action'] = '?act=set_my_sm&amp;cat=' . $cat . '&amp;start=' . $start;
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
