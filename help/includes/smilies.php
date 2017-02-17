<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = App::getContainer()->get(Johncms\Api\UserInterface::class );

// Главное меню каталога смайлов
echo '<div class="phdr"><a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _t('Smilies') . '</div>';

if ($systemUser->isValid()) {
    $mycount = !empty($systemUser->smileys) ? count(unserialize($systemUser->smileys)) : '0';
    echo '<div class="topmenu"><a href="?act=my_smilies">' . _t('My smilies') . '</a> (' . $mycount . ' / ' . $user_smileys . ')</div>';
}

if ($systemUser->rights >= 1) {
    echo '<div class="gmenu"><a href="?act=admsmilies">' . _t('For administration') . '</a> (' . (int)count(glob(ROOT_PATH . 'images/smileys/admin/*.gif')) . ')</div>';
}

$dir = glob(ROOT_PATH . 'images/smileys/user/*', GLOB_ONLYDIR);

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
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo '<a href="?act=usersmilies&amp;cat=' . urlencode($key) . '">' . htmlspecialchars($val) . '</a>' .
        ' (' . count(glob(ROOT_PATH . 'images/smileys/user/' . $key . '/*.{gif,jpg,png}', GLOB_BRACE)) . ')';
    echo '</div>';
    ++$i;
}

echo '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
