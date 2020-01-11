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

// Список своих смайлов
$title = __('My smilies');
$nav_chain->add(__('Smilies'), '?act=smilies');
$nav_chain->add($title);
$data = [];

$smileys = ! empty($user->smileys) ? unserialize($user->smileys, ['allowed_classes' => false]) : [];
$total = count($smileys);

if ($total > $user->config->kmess) {
    $smileys = array_chunk($smileys, $user->config->kmess, true);

    if ($start) {
        $key = ($start - $start % $user->config->kmess) / $user->config->kmess;
        $smileys_view = $smileys[$key];

        if (! count($smileys_view)) {
            $smileys_view = $smileys[0];
        }

        $smileys = $smileys_view;
    } else {
        $smileys = $smileys[0];
    }
}

$i = 0;

$items = [];
foreach ($smileys as $value) {
    $smile = ':' . $value . ':';
    $items[] = [
        'can_del'   => true,
        'lat_smile' => $value,
        'smile'     => $tools->trans($smile),
        'picture'   => $tools->smilies($smile, $user->rights >= 1 ? 1 : 0),
    ];
}

$data['pagination'] = $tools->displayPagination('?act=my_smilies&amp;', $start, $total, $user->config->kmess);
$data['form_action'] = '?act=set_my_sm&amp;start=' . $start;
$data['total'] = $total;
$data['items'] = $items ?? [];
$data['back_url'] = '?act=smilies';

echo $view->render(
    'help::my_smiles_list',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
