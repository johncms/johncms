<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Список своих смайлов
echo '<div class="phdr"><a href="?act=smilies"><b>' . _t('Smilies') . '</b></a> | ' . _t('My smilies') . '</div>';
$smileys = ! empty($user->smileys) ? unserialize($user->smileys, ['allowed_classes' => false]) : [];
$total = count($smileys);

if ($total) {
    echo '<form action="?act=set_my_sm&amp;start=' . $start . '" method="post">';
}

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

foreach ($smileys as $value) {
    $smile = ':' . $value . ':';
    echo($i % 2 ? '<div class="list2">' : '<div class="list1">') .
        '<input type="checkbox" name="delete_sm[]" value="' . $value . '" />&#160;' .
        $tools->smilies($smile, $user->rights >= 1 ? 1 : 0) . '&#160;' . $smile . ' ' . _t('or') . ' ' . $tools->trans($smile) . '</div>';
    $i++;
}

if ($total) {
    echo '<div class="rmenu"><input type="submit" name="delete" value=" ' . _t('Delete') . ' "/></div></form>';
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '<br /><a href="?act=smilies">' . _t('Add Smilies') . '</a></p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . ' / ' . $user_smileys . '</div>';

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=my_smilies&amp;', $start, $total, $user->config->kmess) . '</div>';
}

echo '<p><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></p>';
