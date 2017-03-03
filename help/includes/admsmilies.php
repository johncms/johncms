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

// Каталог Админских Смайлов
if ($systemUser->rights < 1) {
    echo $tools->displayError(_t('Wrong data'), '<a href="?act=smilies">' . _t('Back') . '</a>');
    require('../system/end.php');
    exit;
}

echo '<div class="phdr"><a href="?act=smilies"><b>' . _t('Smilies') . '</b></a> | ' . _t('For administration') . '</div>';
$user_sm = unserialize($systemUser->smileys);

if (!is_array($user_sm)) {
    $user_sm = [];
}

echo '<div class="topmenu"><a href="?act=my_smilies">' . _t('My smilies') . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
    '<form action="?act=set_my_sm&amp;start=' . $start . '&amp;adm" method="post">';
$array = [];
$dir = opendir('../images/smileys/admin');

while (($file = readdir($dir)) !== false) {
    if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
        $array[] = $file;
    }
}

closedir($dir);
$total = count($array);

if ($total > 0) {
    $end = $start + $kmess;

    if ($end > $total) {
        $end = $total;
    }

    for ($i = $start; $i < $end; $i++) {
        $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', $array[$i], 1);
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $smileys = (in_array($smile, $user_sm) ? ''
            : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
        echo $smileys . '<img src="../images/smileys/admin/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . _t('or') . ' :' . $tools->trans($smile) . ':</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="gmenu"><input type="submit" name="add" value=" ' . _t('Add') . ' "/></div></form>';
echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=admsmilies&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="?act=admsmilies" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></p>';
