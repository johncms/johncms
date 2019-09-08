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

require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Закрываем от неавторизованных юзеров
if (!$systemUser->isValid()) {
    require('../system/head.php');
    echo $tools->displayError(_t('For registered users only'));
    require('../system/end.php');
    exit;
}

// Получаем данные пользователя
$user = $tools->getUser(isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : 0);

if (!$user) {
    require('../system/head.php');
    echo $tools->displayError(_t('This User does not exists'));
    require('../system/end.php');
    exit;
}

/**
 * Находится ли выбранный пользователь в контактах и игноре?
 *
 * @param int $id Идентификатор пользователя, которого проверяем
 * @return int Результат запроса:
 *                0 - не в контактах
 *                1 - в контактах
 *                2 - в игноре у меня
 */
function is_contact($id = 0)
{
    global $db, $systemUser;

    static $user_id = null;
    static $return = 0;

    if (!$systemUser->isValid() && !$id) {
        return 0;
    }

    if (is_null($user_id) || $id != $user_id) {
        $user_id = $id;
        $req = $db->query("SELECT * FROM `cms_contact` WHERE `user_id` = '" . $systemUser->id . "' AND `from_id` = '$id'");

        if ($req->rowCount()) {
            $res = $req->fetch();
            if ($res['ban'] == 1) {
                $return = 2;
            } else {
                $return = 1;
            }
        } else {
            $return = 0;
        }
    }

    return $return;
}

// Переключаем режимы работы
$array = [
    'activity'  => 'includes',
    'ban'       => 'includes',
    'edit'      => 'includes',
    'images'    => 'includes',
    'info'      => 'includes',
    'ip'        => 'includes',
    'guestbook' => 'includes',
    'karma'     => 'includes',
    'office'    => 'includes',
    'password'  => 'includes',
    'reset'     => 'includes',
    'settings'  => 'includes',
    'stat'      => 'includes',
];
$path = !empty($array[$act]) ? $array[$act] . '/' : '';

if (isset($array[$act]) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    // Анкета пользователя
    $headmod = 'profile,' . $user['id'];
    $textl = _t('Profile') . ': ' . htmlspecialchars($user['name']);
    require('../system/head.php');
    echo '<div class="phdr"><b>' . ($user['id'] != $systemUser->id ? _t('User Profile') : _t('My Profile')) . '</b></div>';

    // Меню анкеты
    $menu = [];

    if ($user['id'] == $systemUser->id || $systemUser->rights == 9 || ($systemUser->rights == 7 && $systemUser->rights > $user['rights'])) {
        $menu[] = '<a href="?act=edit&amp;user=' . $user['id'] . '">' . _t('Edit') . '</a>';
    }

    if ($user['id'] != $systemUser->id && $systemUser->rights >= 7 && $systemUser->rights > $user['rights']) {
        $menu[] = '<a href="' . $config['homeurl'] . '/admin/index.php?act=usr_del&amp;id=' . $user['id'] . '">' . _t('Delete') . '</a>';
    }

    if ($user['id'] != $systemUser->id && $systemUser->rights > $user['rights']) {
        $menu[] = '<a href="?act=ban&amp;mod=do&amp;user=' . $user['id'] . '">' . _t('Ban') . '</a>';
    }

    if (!empty($menu)) {
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
    }

    //Уведомление о дне рожденья
    if ($user['dayb'] == date('j', time()) && $user['monthb'] == date('n', time())) {
        echo '<div class="gmenu">' . _t('Birthday') . '!!!</div>';
    }

    // Информация о юзере
    $arg = [
        'lastvisit' => 1,
        'iphist'    => 1,
        'header'    => '<b>ID:' . $user['id'] . '</b>',
    ];

    if ($user['id'] != $systemUser->id) {
        $arg['footer'] = '<span class="gray">' . _t('Where?') . ':</span> ' . $tools->displayPlace($user['id'],
                $user['place']);
    }

    echo '<div class="user"><p>' . $tools->displayUser($user, $arg) . '</p></div>';

    // Если юзер ожидает подтверждения регистрации, выводим напоминание
    if ($systemUser->rights >= 7 && !$user['preg'] && empty($user['regadm'])) {
        echo '<div class="rmenu">' . _t('Pending confirmation') . '</div>';
    }

    // Карма
    if ($set_karma['on']) {
        $karma = $user['karma_plus'] - $user['karma_minus'];

        if ($karma > 0) {
            $images = ($user['karma_minus'] ? ceil($user['karma_plus'] / $user['karma_minus']) : $user['karma_plus']) > 10 ? '2' : '1';
            echo '<div class="gmenu">';
        } else {
            if ($karma < 0) {
                $images = ($user['karma_plus'] ? ceil($user['karma_minus'] / $user['karma_plus']) : $user['karma_minus']) > 10 ? '-2' : '-1';
                echo '<div class="rmenu">';
            } else {
                $images = 0;
                echo '<div class="menu">';
            }
        }

        echo '<table  width="100%"><tr><td width="22" valign="top"><img src="' . $config['homeurl'] . '/images/k_' . $images . '.gif"/></td><td>' .
            '<b>' . _t('Karma') . ' (' . $karma . ')</b>' .
            '<div class="sub">' .
            '<span class="green"><a href="?act=karma&amp;user=' . $user['id'] . '&amp;type=1">' . _t('For') . ' (' . $user['karma_plus'] . ')</a></span> | ' .
            '<span class="red"><a href="?act=karma&amp;user=' . $user['id'] . '">' . _t('Against') . ' (' . $user['karma_minus'] . ')</a></span>';

        if ($user['id'] != $systemUser->id) {
            if (!$systemUser->karma_off && (!$user['rights'] || ($user['rights'] && !$set_karma['adm'])) && $user['ip'] != $systemUser->ip) {
                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $systemUser->id . "' AND `time` >= '" . $systemUser->karma_time . "'")->fetchColumn();
                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $systemUser->id . "' AND `karma_user` = '" . $user['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if (empty($systemUser->ban) && $systemUser->postforum >= $set_karma['forum'] && $systemUser->total_on_site >= $set_karma['karma_time'] && ($set_karma['karma_points'] - $sum) > 0 && !$count) {
                    echo '<br /><a href="?act=karma&amp;mod=vote&amp;user=' . $user['id'] . '">' . _t('Vote') . '</a>';
                }
            }
        } else {
            $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $systemUser->id . "' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total_karma > 0) {
                echo '<br /><a href="?act=karma&amp;mod=new">' . _t('New reviews') . '</a> (' . $total_karma . ')';
            }
        }
        echo '</div></td></tr></table></div>';
    }

    // Меню выбора
    $total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    echo '<div class="list2"><p>' .
        '<div>' . $tools->image('contacts.png') . '<a href="?act=info&amp;user=' . $user['id'] . '">' . _t('Information') . '</a></div>' .
        '<div>' . $tools->image('activity.gif') . '<a href="?act=activity&amp;user=' . $user['id'] . '">' . _t('Activity') . '</a></div>' .
        '<div>' . $tools->image('rate.gif') . '<a href="?act=stat&amp;user=' . $user['id'] . '">' . _t('Statistic') . '</a></div>';
    $bancount = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    if ($bancount) {
        echo '<div><img src="../images/block.gif" width="16" height="16"/>&#160;<a href="?act=ban&amp;user=' . $user['id'] . '">' . _t('Violations') . '</a> (' . $bancount . ')</div>';
    }

    echo '<br />' .
        '<div>' . $tools->image('photo.gif') . '<a href="../album/index.php?act=list&amp;user=' . $user['id'] . '">' . _t('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
        '<div>' . $tools->image('guestbook.gif') . '<a href="?act=guestbook&amp;user=' . $user['id'] . '">' . _t('Guestbook') . '</a>&#160;(' . $user['comm_count'] . ')</div>' .
        '</p></div>';
    if ($user['id'] != $systemUser->id) {
        echo '<div class="menu"><p>';
        // Контакты
        if (is_contact($user['id']) != 2) {
            if (!is_contact($user['id'])) {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?id=' . $user['id'] . '">' . _t('Add to Contacts') . '</a></div>';
            } else {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=deluser&amp;id=' . $user['id'] . '">' . _t('Remove from Contacts') . '</a></div>';
            }
        }

        if (is_contact($user['id']) != 2) {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;add">' . _t('Block User') . '</a></div>';
        } else {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;del">' . _t('Unlock User') . '</a></div>';
        }

        echo '</p>';

        if (!$tools->isIgnor($user['id'])
            && is_contact($user['id']) != 2
            && !isset($systemUser->ban['1'])
            && !isset($systemUser->ban['3'])
        ) {
            echo '<p><form action="../mail/index.php?act=write&amp;id=' . $user['id'] . '" method="post"><input type="submit" value="' . _t('Write') . '" style="margin-left: 18px"/></form></p>';
        }

        echo '</div>';
    }
}

require_once('../system/end.php');
