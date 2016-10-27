<?php

define('_IN_JOHNCMS', 1);

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);

require('../incfiles/core.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Закрываем от неавторизованных юзеров
if (!$user_id) {
    require('../system/head.php');
    echo functions::display_error(_t('For registered users only'));
    require('../system/end.php');
    exit;
}

// Получаем данные пользователя
$user = functions::get_user(isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : 0);

if (!$user) {
    require('../system/head.php');
    echo functions::display_error(_t('This User does not exists'));
    require('../system/end.php');
    exit;
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
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Анкета пользователя
    $headmod = 'profile,' . $user['id'];
    $textl = _t('Profile') . ': ' . htmlspecialchars($user['name']);
    require('../system/head.php');
    echo '<div class="phdr"><b>' . ($user['id'] != $user_id ? _t('User Profile') : _t('My Profile')) . '</b></div>';

    // Меню анкеты
    $menu = [];

    if ($user['id'] == $user_id || $rights == 9 || ($rights == 7 && $rights > $user['rights'])) {
        $menu[] = '<a href="?act=edit&amp;user=' . $user['id'] . '">' . _t('Edit') . '</a>';
    }

    if ($user['id'] != $user_id && $rights >= 7 && $rights > $user['rights']) {
        $menu[] = '<a href="' . $config['homeurl'] . '/admin/index.php?act=usr_del&amp;id=' . $user['id'] . '">' . _t('Delete') . '</a>';
    }

    if ($user['id'] != $user_id && $rights > $user['rights']) {
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

    if ($user['id'] != core::$user_id) {
        $arg['footer'] = '<span class="gray">' . _t('Where?') . ':</span> ' . functions::display_place($user['id'],
                $user['place']);
    }

    echo '<div class="user"><p>' . functions::display_user($user, $arg) . '</p></div>';

    // Если юзер ожидает подтверждения регистрации, выводим напоминание
    if ($rights >= 7 && !$user['preg'] && empty($user['regadm'])) {
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

        if ($user['id'] != $user_id) {
            if (!$datauser['karma_off'] && (!$user['rights'] || ($user['rights'] && !$set_karma['adm'])) && $user['ip'] != $datauser['ip']) {
                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '" . $datauser['karma_time'] . "'")->fetchColumn();
                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '" . $user['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if (!$ban && $datauser['postforum'] >= $set_karma['forum'] && $datauser['total_on_site'] >= $set_karma['karma_time'] && ($set_karma['karma_points'] - $sum) > 0 && !$count) {
                    echo '<br /><a href="?act=karma&amp;mod=vote&amp;user=' . $user['id'] . '">' . _t('Vote') . '</a>';
                }
            }
        } else {
            $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total_karma > 0) {
                echo '<br /><a href="?act=karma&amp;mod=new">' . _t('New reviews') . '</a> (' . $total_karma . ')';
            }
        }
        echo '</div></td></tr></table></div>';
    }

    // Меню выбора
    $total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    echo '<div class="list2"><p>' .
        '<div>' . functions::image('contacts.png') . '<a href="?act=info&amp;user=' . $user['id'] . '">' . _t('Information') . '</a></div>' .
        '<div>' . functions::image('activity.gif') . '<a href="?act=activity&amp;user=' . $user['id'] . '">' . _t('Activity') . '</a></div>' .
        '<div>' . functions::image('rate.gif') . '<a href="?act=stat&amp;user=' . $user['id'] . '">' . _t('Statistic') . '</a></div>';
    $bancount = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    if ($bancount) {
        echo '<div><img src="../images/block.gif" width="16" height="16"/>&#160;<a href="?act=ban&amp;user=' . $user['id'] . '">' . _t('Violations') . '</a> (' . $bancount . ')</div>';
    }

    echo '<br />' .
        '<div>' . functions::image('photo.gif') . '<a href="../album/index.php?act=list&amp;user=' . $user['id'] . '">' . _t('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
        '<div>' . functions::image('guestbook.gif') . '<a href="?act=guestbook&amp;user=' . $user['id'] . '">' . _t('Guestbook') . '</a>&#160;(' . $user['comm_count'] . ')</div>' .
        '</p></div>';
    if ($user['id'] != $user_id) {
        echo '<div class="menu"><p>';
        // Контакты
        if (functions::is_contact($user['id']) != 2) {
            if (!functions::is_contact($user['id'])) {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?id=' . $user['id'] . '">' . _t('Add to Contacts') . '</a></div>';
            } else {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=deluser&amp;id=' . $user['id'] . '">' . _t('Remove from Contacts') . '</a></div>';
            }
        }

        if (functions::is_contact($user['id']) != 2) {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;add">' . _t('Block User') . '</a></div>';
        } else {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;del">' . _t('Unlock User') . '</a></div>';
        }

        echo '</p>';

        if (!functions::is_ignor($user['id']) && functions::is_contact($user['id']) != 2 && empty($ban['1']) && empty($ban['3'])) {
            echo '<p><form action="../mail/index.php?act=write&amp;id=' . $user['id'] . '" method="post"><input type="submit" value="' . _t('Write') . '" style="margin-left: 18px"/></form></p>';
        }

        echo '</div>';
    }
}

require_once('../system/end.php');
