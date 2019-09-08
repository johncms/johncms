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

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

require_once('../system/bootstrap.php');
$headmod = 'mail';

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

//Проверка авторизации
if (!$systemUser->isValid()) {
    header('Location: ' . $config->homeurl . '/?err');
    exit;
}

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

function formatsize($size)
{
    // Форматирование размера файлов
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' Gb';
    } elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' Mb';
    } elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' Kb';
    } else {
        $size = $size . ' b';
    }

    return $size;
}

// Массив подключаемых функций
$mods = [
    'ignor',
    'write',
    'systems',
    'deluser',
    'load',
    'files',
    'input',
    'output',
    'delete',
    'new',
];

//Проверка выбора функции
if ($act && ($key = array_search($act, $mods)) !== false && file_exists('includes/' . $mods[$key] . '.php')) {
    require('includes/' . $mods[$key] . '.php');
} else {
    $textl = _t('Mail');
    require_once('../system/head.php');
    echo '<div class="phdr"><b>' . _t('Contacts') . '</b></div>';

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\ToolsInterface $tools */
    $tools = $container->get(Johncms\Api\ToolsInterface::class);

    if ($id) {
        $req = $db->query("SELECT * FROM `users` WHERE `id` = '$id'");

        if (!$req->rowCount()) {
            echo $tools->displayError(_t('User does not exists'));
            require_once("../system/end.php");
            exit;
        }

        $res = $req->fetch();

        if ($id == $systemUser->id) {
            echo '<div class="rmenu">' . _t('You cannot add yourself as a contact') . '</div>';
        } else {
            //Добавляем в заблокированные
            if (isset($_POST['submit'])) {
                $q = $db->query("SELECT * FROM `cms_contact` WHERE `user_id` = " . $systemUser->id . " AND `from_id` = " . $id);

                if (!$q->rowCount()) {
                    $db->query("INSERT INTO `cms_contact` SET
					`user_id` = " . $systemUser->id . ",
					`from_id` = " . $id . ",
					`time` = " . time());
                }
                echo '<div class="gmenu"><p>' . _t('User has been added to your contact list') . '</p><p><a href="index.php">' . _t('Continue') . '</a></p></div>';
            } else {
                echo '<div class="menu">' .
                    '<form action="index.php?id=' . $id . '&amp;add" method="post">' .
                    '<div><p>' . _t('You really want to add contact?') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Add') . '"/></p>' .
                    '</div></form></div>';
            }
        }
    } else {
        echo '<div class="topmenu"><b>' . _t('My Contacts') . '</b> | <a href="index.php?act=ignor">' . _t('Blocklist') . '</a></div>';
        //Получаем список контактов
        $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $systemUser->id . "' AND `ban`!='1'")->fetchColumn();

        if ($total) {
            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?', $start, $total, $kmess) . '</div>';
            }

            $req = $db->query("SELECT `users`.*, `cms_contact`.`from_id` AS `id`
                FROM `cms_contact`
			    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
			    WHERE `cms_contact`.`user_id`='" . $systemUser->id . "'
			    AND `cms_contact`.`ban`!='1'
			    ORDER BY `users`.`name` ASC
			    LIMIT $start, $kmess"
            );

            for ($i = 0; ($row = $req->fetch()) !== false; ++$i) {
                echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
                $subtext = '<a href="index.php?act=write&amp;id=' . $row['id'] . '">' . _t('Correspondence') . '</a> | <a href="index.php?act=deluser&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a> | <a href="index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . _t('Block User') . '</a>';
                $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $systemUser->id . "') OR (`user_id`='" . $systemUser->id . "' AND `from_id`='{$row['id']}')) AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $systemUser->id . "'")->rowCount();
                $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='" . $systemUser->id . "' AND `read`='0' AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $systemUser->id . "'")->rowCount();
                $arg = [
                    'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                    'sub'    => $subtext,
                ];
                echo $tools->displayUser($row, $arg);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('index.php?', $start, $total, $kmess) . '</div>';
            echo '<p><form action="index.php" method="get">
				<input type="text" name="page" size="2"/>
				<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
        }

        echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
    }
}

require_once(ROOT_PATH . 'system/end.php');
