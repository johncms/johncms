<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Config\Config;
use Johncms\Api\ToolsInterface;
use Johncms\System\Users\User;
use Johncms\View\Extension\Assets;
use Johncms\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var Assets $assets
 * @var Config $config
 * @var PDO $db
 * @var ToolsInterface $tools
 * @var User $user
 * @var Render $view
 */

$assets = di(Assets::class);
$config = di(Config::class);
$db = di(PDO::class);
$tools = di(ToolsInterface::class);
$user = di(User::class);
$view = di(Render::class);

// Регистрируем языки модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

// Закрываем от неавторизованных юзеров
if (! $user->isValid()) {
    echo $tools->displayError(_t('For registered users only'));
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

// Получаем данные пользователя
$foundUser = $tools->getUser(isset($_REQUEST['user']) ? abs((int) ($_REQUEST['user'])) : 0);

if (! $foundUser) {
    echo $tools->displayError(_t('This User does not exists'));
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
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

    if (! $systemUser->isValid() && ! $id) {
        return 0;
    }

    if (null === $user_id || $id != $user_id) {
        $user_id = $id;
        $req = $db->query("SELECT * FROM `cms_contact` WHERE `user_id` = '" . $systemUser->id . "' AND `from_id` = '${id}'");

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
$mods = [
    'activity',
    'ban',
    'edit',
    'images',
    'info',
    'ip',
    'guestbook',
    'karma',
    'office',
    'password',
    'reset',
    'settings',
    'stat',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    // Анкета пользователя
    echo '<div class="phdr"><b>' . ($foundUser['id'] != $user->id ? _t('User Profile') : _t('My Profile')) . '</b></div>';

    // Меню анкеты
    $menu = [];

    if ($foundUser['id'] == $user->id || $user->rights == 9 || ($user->rights == 7 && $user->rights > $foundUser['rights'])) {
        $menu[] = '<a href="?act=edit&amp;user=' . $foundUser['id'] . '">' . _t('Edit') . '</a>';
    }

    if ($foundUser['id'] != $user->id && $user->rights >= 7 && $user->rights > $foundUser['rights']) {
        $menu[] = '<a href="' . $config['homeurl'] . '/admin/?act=usr_del&amp;id=' . $foundUser['id'] . '">' . _t('Delete') . '</a>';
    }

    if ($foundUser['id'] != $user->id && $user->rights > $foundUser['rights']) {
        $menu[] = '<a href="?act=ban&amp;mod=do&amp;user=' . $foundUser['id'] . '">' . _t('Ban') . '</a>';
    }

    if (! empty($menu)) {
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
    }

    //Уведомление о дне рожденья
    if ($foundUser['dayb'] == date('j', time()) && $foundUser['monthb'] == date('n', time())) {
        echo '<div class="gmenu">' . _t('Birthday') . '!</div>';
    }

    // Информация о юзере
    $arg = [
        'lastvisit' => 1,
        'iphist'    => 1,
        'header'    => '<b>ID:' . $foundUser['id'] . '</b>',
    ];

    if ($foundUser['id'] != $user->id) {
        $arg['footer'] = '<span class="gray">' . _t('Where?') . ':</span> ' . $tools->displayPlace($foundUser['place'], $foundUser['id']);
    }

    echo '<div class="user"><p>' . $tools->displayUser($foundUser, $arg) . '</p></div>';

    // Если юзер ожидает подтверждения регистрации, выводим напоминание
    if ($user->rights >= 7 && ! $foundUser['preg'] && empty($foundUser['regadm'])) {
        echo '<div class="rmenu">' . _t('Pending confirmation') . '</div>';
    }

    // Карма
    if ($config->karma['on']) {
        $karma = $foundUser['karma_plus'] - $foundUser['karma_minus'];

        if ($karma > 0) {
            $images = ($foundUser['karma_minus'] ? ceil($foundUser['karma_plus'] / $foundUser['karma_minus']) : $foundUser['karma_plus']) > 10 ? '2' : '1';
            echo '<div class="gmenu">';
        } else {
            if ($karma < 0) {
                $images = ($foundUser['karma_plus'] ? ceil($foundUser['karma_minus'] / $foundUser['karma_plus']) : $foundUser['karma_minus']) > 10 ? '-2' : '-1';
                echo '<div class="rmenu">';
            } else {
                $images = 0;
                echo '<div class="menu">';
            }
        }

        echo '<table  width="100%"><tr><td width="22" valign="top"><img src="' . $assets->url('images/old/k_' . $images . '.gif') . '"/></td><td>' .
            '<b>' . _t('Karma') . ' (' . $karma . ')</b>' .
            '<div class="sub">' .
            '<span class="green"><a href="?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=1">' . _t('For') . ' (' . $foundUser['karma_plus'] . ')</a></span> | ' .
            '<span class="red"><a href="?act=karma&amp;user=' . $foundUser['id'] . '">' . _t('Against') . ' (' . $foundUser['karma_minus'] . ')</a></span>';

        if ($foundUser['id'] != $user->id) {
            if (! $user->karma_off && (! $foundUser['rights'] || ($foundUser['rights'] && ! $set_karma['adm'])) && $foundUser['ip'] != $user->ip) {
                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `time` >= '" . $user->karma_time . "'")->fetchColumn();
                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `karma_user` = '" . $foundUser['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if (empty($user->ban) && $user->postforum >= $set_karma['forum'] && $user->total_on_site >= $set_karma['karma_time'] && ($set_karma['karma_points'] - $sum) > 0 && ! $count) {
                    echo '<br /><a href="?act=karma&amp;mod=vote&amp;user=' . $foundUser['id'] . '">' . _t('Vote') . '</a>';
                }
            }
        } else {
            $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total_karma > 0) {
                echo '<br /><a href="?act=karma&amp;mod=new">' . _t('New reviews') . '</a> (' . $total_karma . ')';
            }
        }
        echo '</div></td></tr></table></div>';
    }

    // Меню выбора
    $total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();
    echo '<div class="list2"><p>' .
        '<div><img src="' . $assets->url('images/old/contacts.png') . '" alt="" class="icon"><a href="?act=info&amp;user=' . $foundUser['id'] . '">' . _t('Information') . '</a></div>' .
        '<div><img src="' . $assets->url('images/old/activity.gif') . '" alt="" class="icon"><a href="?act=activity&amp;user=' . $foundUser['id'] . '">' . _t('Activity') . '</a></div>' .
        '<div><img src="' . $assets->url('images/old/rate.gif') . '" alt="" class="icon"><a href="?act=stat&amp;user=' . $foundUser['id'] . '">' . _t('Statistic') . '</a></div>';
    $bancount = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

    if ($bancount) {
        echo '<div><img src="' . $assets->url('images/old/block.gif') . '" alt="" class="icon"><a href="?act=ban&amp;user=' . $foundUser['id'] . '">' . _t('Violations') . '</a> (' . $bancount . ')</div>';
    }

    echo '<br />' .
        '<div><img src="' . $assets->url('images/old/photo.gif') . '" alt="" class="icon"><a href="../album/?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
        '<div><img src="' . $assets->url('images/old/guestbook.gif') . '" alt="" class="icon"><a href="?act=guestbook&amp;user=' . $foundUser['id'] . '">' . _t('Guestbook') . '</a>&#160;(' . $foundUser['comm_count'] . ')</div>' .
        '</p></div>';
    if ($foundUser['id'] != $user->id) {
        echo '<div class="menu"><p>';
        // Контакты
        if (is_contact($foundUser['id']) != 2) {
            if (! is_contact($foundUser['id'])) {
                echo '<div><img src="' . $assets->url('images/old/users.png') . '" alt="" class="icon"><a href="../mail/?id=' . $foundUser['id'] . '">' . _t('Add to Contacts') . '</a></div>';
            } else {
                echo '<div><img src="' . $assets->url('images/old/users.png') . '" alt="" class="icon"><a href="../mail/?act=deluser&amp;id=' . $foundUser['id'] . '">' . _t('Remove from Contacts') . '</a></div>';
            }
        }

        if (is_contact($foundUser['id']) != 2) {
            echo '<div><img src="' . $assets->url('images/old/del.png') . '" alt="" class="icon"><a href="../mail/?act=ignor&amp;id=' . $foundUser['id'] . '&amp;add">' . _t('Block User') . '</a></div>';
        } else {
            echo '<div><img src="' . $assets->url('images/old/del.png') . '" alt="" class="icon"><a href="../mail/?act=ignor&amp;id=' . $foundUser['id'] . '&amp;del">' . _t('Unlock User') . '</a></div>';
        }

        echo '</p>';

        if (! $tools->isIgnor($foundUser['id'])
            && is_contact($foundUser['id']) != 2
            && ! isset($user->ban['1'])
            && ! isset($user->ban['3'])
        ) {
            echo '<p><form action="../mail/?act=write&amp;id=' . $foundUser['id'] . '" method="post"><input type="submit" value="' . _t('Write') . '" style="margin-left: 18px"/></form></p>';
        }

        echo '</div>';
    }

    $textl = _t('Profile') . ': ' . htmlspecialchars($foundUser['name']);
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => $textl ?? '',
        'content' => ob_get_clean(),
    ]
);
