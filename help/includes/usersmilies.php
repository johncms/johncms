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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Каталог пользовательских Смайлов
$dir = glob(ROOT_PATH . 'images/smileys/user/*', GLOB_ONLYDIR);

foreach ($dir as $val) {
    $val = explode('/', $val);
    $cat_list[] = array_pop($val);
}

$cat = isset($_GET['cat']) && in_array(trim($_GET['cat']), $cat_list) ? trim($_GET['cat']) : $cat_list[0];
$smileys = glob(ROOT_PATH . 'images/smileys/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE);
$total = count($smileys);
$end = $start + $kmess;

if ($end > $total) {
    $end = $total;
}

echo '<div class="phdr"><a href="?act=smilies"><b>' . _t('Smilies') . '</b></a> | ' .
    (array_key_exists($cat, smiliesCat()) ? smiliesCat()[$cat] : ucfirst(htmlspecialchars($cat))) .
    '</div>';

if ($total) {
    if ($systemUser->isValid()) {
        $user_sm = isset($systemUser->smileys) ? unserialize($systemUser->smileys) : '';

        if (!is_array($user_sm)) {
            $user_sm = [];
        }

        echo '<div class="topmenu">' .
            '<a href="?act=my_smilies">' . _t('My smilies') . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
            '<form action="?act=set_my_sm&amp;cat=' . $cat . '&amp;start=' . $start . '" method="post">';
    }

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=usersmilies&amp;cat=' . urlencode($cat) . '&amp;', $start, $total, $kmess) . '</div>';
    }

    for ($i = $start; $i < $end; $i++) {
        $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', basename($smileys[$i], 1));
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

        if ($systemUser->isValid()) {
            echo(in_array($smile, $user_sm) ? '' : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
        }

        echo '<img src="../images/smileys/user/' . $cat . '/' . basename($smileys[$i]) . '" alt="" />&#160;:' . $smile . ': ' . _t('or') . ' :' . $tools->trans($smile) . ':';
        echo '</div>';
    }

    if ($systemUser->isValid()) {
        echo '<div class="gmenu"><input type="submit" name="add" value=" ' . _t('Add') . ' "/></div></form>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=usersmilies&amp;cat=' . urlencode($cat) . '&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="?act=usersmilies&amp;cat=' . urlencode($cat) . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></p>';
