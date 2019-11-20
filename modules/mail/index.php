<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ConfigInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var ConfigInterface    $config
 * @var PDO                $db
 * @var ToolsInterface     $tools
 * @var UserInterface      $user
 * @var Engine             $view
 */

$config = di(ConfigInterface::class);
$db = di(PDO::class);
$tools = di(ToolsInterface::class);
$user = di(UserInterface::class);
$view = di(Engine::class);

// Регистрируем языки модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

//Проверка авторизации
if (! $user->isValid()) {
    header('Location: ' . $config->homeurl);
    exit;
}

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
if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    $textl = _t('Mail');
    echo '<div class="phdr"><b>' . _t('Contacts') . '</b></div>';

    if ($id) {
        $req = $db->query("SELECT * FROM `users` WHERE `id` = '${id}'");

        if (! $req->rowCount()) {
            echo $view->render('system::app/old_content', [
                'title'   => $textl,
                'content' => $tools->displayError(_t('User does not exists')),
            ]);
            exit;
        }

        $res = $req->fetch();

        if ($id == $user->id) {
            echo '<div class="rmenu">' . _t('You cannot add yourself as a contact') . '</div>';
        } else {
            //Добавляем в заблокированные
            if (isset($_POST['submit'])) {
                $q = $db->query('SELECT * FROM `cms_contact` WHERE `user_id` = ' . $user->id . ' AND `from_id` = ' . $id);

                if (! $q->rowCount()) {
                    $db->query('INSERT INTO `cms_contact` SET
					`user_id` = ' . $user->id . ',
					`from_id` = ' . $id . ',
					`time` = ' . time());
                }
                echo '<div class="gmenu"><p>' . _t('User has been added to your contact list') . '</p><p><a href="./">' . _t('Continue') . '</a></p></div>';
            } else {
                echo '<div class="menu">' .
                    '<form action="?id=' . $id . '&amp;add" method="post">' .
                    '<div><p>' . _t('You really want to add contact?') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Add') . '"/></p>' .
                    '</div></form></div>';
            }
        }
    } else {
        echo '<div class="topmenu"><b>' . _t('My Contacts') . '</b> | <a href="?act=ignor">' . _t('Blocklist') . '</a></div>';
        //Получаем список контактов
        $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`!='1'")->fetchColumn();

        if ($total) {
            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
            }

            $req = $db->query("SELECT `users`.*, `cms_contact`.`from_id` AS `id`
                FROM `cms_contact`
			    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
			    WHERE `cms_contact`.`user_id`='" . $user->id . "'
			    AND `cms_contact`.`ban`!='1'
			    ORDER BY `users`.`name` ASC
			    LIMIT ${start}, " . $user->config->kmess);

            for ($i = 0; ($row = $req->fetch()) !== false; ++$i) {
                echo ($i % 2) ? '<div class="list1">' : '<div class="list2">';
                $subtext = '<a href="?act=write&amp;id=' . $row['id'] . '">' . _t('Correspondence') . '</a> | <a href="?act=deluser&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a> | <a href="?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . _t('Block User') . '</a>';
                $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='{$row['id']}')) AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $user->id . "'")->rowCount();
                $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='" . $user->id . "' AND `read`='0' AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $user->id . "'")->rowCount();
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

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
            echo '<p><form method="get">
				<input type="text" name="page" size="2"/>
				<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
        }

        echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
    }
}

echo $view->render('system::app/old_content', [
    'title'   => $textl,
    'content' => ob_get_clean(),
]);
