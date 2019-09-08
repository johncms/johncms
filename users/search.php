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

define('_IN_JOHNCMS', 1);

$headmod = 'usersearch';
require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

$textl = _t('User Search');
require('../system/head.php');

// Принимаем данные, выводим форму поиска
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
$search = $search_post ? $search_post : $search_get;
echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('User Search') . '</div>' .
    '<form action="search.php" method="post">' .
    '<div class="gmenu"><p>' .
    '<input type="text" name="search" value="' . $tools->checkout($search) . '" />' .
    '<input type="submit" value="' . _t('Search') . '" name="submit" />' .
    '</p></div></form>';

// Проверям на ошибки
$error = [];

if (!empty($search) && (mb_strlen($search) < 2 || mb_strlen($search) > 20)) {
    $error[] = _t('Nickname') . ': ' . _t('Invalid length');
}

if (preg_match("/[^1-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $tools->rusLat($search))) {
    $error[] = _t('Nickname') . ': ' . _t('Invalid characters');
}

if ($search && !$error) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Выводим результаты поиска
    $search_db = $tools->rusLat($search);
    $search_db = strtr($search_db, [
        '_' => '\\_',
        '%' => '\\%',
    ]);
    $search_db = '%' . $search_db . '%';
    $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `name_lat` LIKE " . $db->quote($search_db))->fetchColumn();
    echo '<div class="phdr"><b>' . _t('Searching results') . '</b></div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('search.php?search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query("SELECT * FROM `users` WHERE `name_lat` LIKE " . $db->quote($search_db) . " ORDER BY `name` ASC LIMIT $start, $kmess");
        $i = 0;
        while ($res = $req->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $res['name'] = mb_strlen($search) < 2 ? $res['name'] : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $res['name']);
            echo $tools->displayUser($res);
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="menu"><p>' . _t('Your search did not match any results') . '</p></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('search.php?search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>' .
            '<p><form action="search.php?search=' . urlencode($search) . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }
} else {
    if ($error) {
        echo $tools->displayError($error);
    }
    echo '<div class="phdr"><small>' . _t('Search by Nickname are case insensitive. For example <strong>UsEr</strong> and <strong>user</strong> are identical.') . '</small></div>';
}

echo '<p>' . ($search && !$error ? '<a href="search.php">' . _t('New search') . '</a><br />' : '') .
    '<a href="index.php">' . _t('Back') . '</a></p>';

require('../system/end.php');
