<?php

define('_IN_JOHNCMS', 1);

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);

$headmod = 'guestbook';
require('../incfiles/core.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var PDO $db */
$db = $container->get(PDO::class);

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Проверяем права доступа в Админ-Клуб
if (isset($_SESSION['ga']) && $rights < 1) {
    unset($_SESSION['ga']);
}

// Задаем заголовки страницы
$textl = isset($_SESSION['ga']) ? _t('Admin Club') : _t('Guestbook');
require('../incfiles/head.php');

// Если гостевая закрыта, выводим сообщение и закрываем доступ (кроме Админов)
if (!$config['mod_guest'] && $rights < 7) {
    echo '<div class="rmenu"><p>' . _t('Guestbook is closed') . '</p></div>';
    require('../incfiles/end.php');
    exit;
}

switch ($act) {
    case 'delpost':
        // Удаление отдельного поста
        if ($rights >= 6 && $id) {
            if (isset($_GET['yes'])) {
                $db->exec('DELETE FROM `guest` WHERE `id` = ' . $id);
                header("Location: index.php");
            } else {
                echo '<div class="phdr"><a href="index.php"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Delete message') . '</div>' .
                    '<div class="rmenu"><p>' . _t('Do you really want to delete?') . '?<br>' .
                    '<a href="index.php?act=delpost&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | ' .
                    '<a href="index.php">' . _t('Cancel') . '</a></p></div>';
            }
        }
        break;

    case 'say':
        // Добавление нового поста
        $admset = isset($_SESSION['ga']) ? 1 : 0; // Задаем куда вставляем, в Админ клуб (1), или в Гастивуху (0)
        // Принимаем и обрабатываем данные
        $name = isset($_POST['name']) ? functions::checkin(mb_substr(trim($_POST['name']), 0, 20)) : '';
        $msg = isset($_POST['msg']) ? functions::checkin(mb_substr(trim($_POST['msg']), 0, 5000)) : '';
        $trans = isset($_POST['msgtrans']) ? 1 : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $from = $user_id ? $login : $name;
        // Проверяем на ошибки
        $error = [];
        $flood = false;

        if (!isset($_POST['token']) || !isset($_SESSION['token']) || $_POST['token'] != $_SESSION['token']) {
            $error[] = _t('Wrong data');
        }

        if (!$user_id && empty($name)) {
            $error[] = _t('You have not entered a name');
        }

        if (empty($msg)) {
            $error[] = _t('You have not entered the message');
        }

        if ($ban['1'] || $ban['13']) {
            $error[] = _t('Access forbidden');
        }

        // CAPTCHA для гостей
        if (!$user_id && (empty($code) || mb_strlen($code) < 4 || $code != $_SESSION['code'])) {
            $error[] = _t('The security code is not correct');
        }

        unset($_SESSION['code']);

        if ($user_id) {
            // Антифлуд для зарегистрированных пользователей
            $flood = functions::antiflood();
        } else {
            // Антифлуд для гостей
            $req = $db->query("SELECT `time` FROM `guest` WHERE `ip` = '$ip' AND `browser` = " . $db->quote($container->get('vars')->getUserAgent()) . " AND `time` > '" . (time() - 60) . "'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $flood = time() - $res['time'];
            }
        }

        if ($flood) {
            $error = sprintf(_t('You cannot add the message so often. Please, wait %d seconds.'), $flood);
        }

        if (!$error) {
            // Проверка на одинаковые сообщения
            $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '$user_id' ORDER BY `time` DESC");
            $res = $req->fetch();

            if ($res['text'] == $msg) {
                header("location: index.php");
                exit;
            }
        }

        if (!$error) {
            // Вставляем сообщение в базу
            $db->prepare("INSERT INTO `guest` SET
                `adm` = ?,
                `time` = ?,
                `user_id` = ?,
                `name` = ?,
                `text` = ?,
                `ip` = ?,
                `browser` = ?,
                `otvet` = ''
            ")->execute([
                $admset,
                time(),
                ($user_id ? $user_id : 0),
                $from,
                $msg,
                $container->get('vars')->getIp(),
                $container->get('vars')->getUserAgent(),
            ]);

            // Фиксируем время последнего поста (антиспам)
            if ($user_id) {
                $postguest = $datauser['postguest'] + 1;
                $db->exec("UPDATE `users` SET `postguest` = '$postguest', `lastpost` = '" . time() . "' WHERE `id` = '$user_id'");
            }

            header('location: index.php');
        } else {
            echo functions::display_error($error, '<a href="index.php">' . _t('Back') . '</a>');
        }
        break;

    case
    'otvet':
        // Добавление "ответа Админа"
        if ($rights >= 6 && $id) {
            if (isset($_POST['submit'])
                && isset($_POST['token'])
                && isset($_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $reply = isset($_POST['otv']) ? functions::checkin(mb_substr(trim($_POST['otv']), 0, 5000)) : '';
                $db->exec("UPDATE `guest` SET
                    `admin` = '$login',
                    `otvet` = " . $db->quote($reply) . ",
                    `otime` = '" . time() . "'
                    WHERE `id` = '$id'
                ");
                header("location: index.php");
            } else {
                echo '<div class="phdr"><a href="index.php"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Reply') . '</div>';
                $req = $db->query("SELECT * FROM `guest` WHERE `id` = '$id'");
                $res = $req->fetch();
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;

                echo '<div class="menu">' .
                    '<div class="quote"><b>' . $res['name'] . '</b>' .
                    '<br />' . functions::checkout($res['text']) . '</div>' .
                    '<form name="form" action="index.php?act=otvet&amp;id=' . $id . '" method="post">' .
                    '<p><h3>' . _t('Reply') . '</h3>' . bbcode::auto_bb('form', 'otv') .
                    '<textarea rows="' . $set_user['field_h'] . '" name="otv">' . functions::checkout($res['otvet']) . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Reply') . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
            }
        }
        break;

    case 'edit':
        // Редактирование поста
        if ($rights >= 6 && $id) {
            if (isset($_POST['submit'])
                && isset($_POST['token'])
                && isset($_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $res = $db->query("SELECT `edit_count` FROM `guest` WHERE `id`='$id'")->fetch();
                $edit_count = $res['edit_count'] + 1;
                $msg = isset($_POST['msg']) ? functions::checkin(mb_substr(trim($_POST['msg']), 0, 5000)) : '';

                $db->prepare('
                  UPDATE `guest` SET
                  `text` = ?,
                  `edit_who` = ?,
                  `edit_time` = ?,
                  `edit_count` = ?
                  WHERE `id` = ?
                ')->execute([
                    $msg,
                    $login,
                    time(),
                    $edit_count,
                    $id,
                ]);

                header("location: index.php");
            } else {
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                $res = $db->query("SELECT * FROM `guest` WHERE `id` = '$id'")->fetch();
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
                echo '<div class="phdr"><a href="index.php"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Edit') . '</div>' .
                    '<div class="rmenu">' .
                    '<form name="form" action="index.php?act=edit&amp;id=' . $id . '" method="post">' .
                    '<p><b>' . _t('Author') . ':</b> ' . $res['name'] . '</p><p>';
                echo bbcode::auto_bb('form', 'msg');
                echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . $text . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
            }
        }
        break;

    case 'clean':
        // Очистка Гостевой
        if ($rights >= 7) {
            if (isset($_POST['submit'])) {
                // Проводим очистку Гостевой, согласно заданным параметрам
                $adm = isset($_SESSION['ga']) ? 1 : 0;
                $cl = isset($_POST['cl']) ? intval($_POST['cl']) : '';

                switch ($cl) {
                    case '1':
                        // Чистим сообщения, старше 1 дня
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm' AND `time` < '" . (time() - 86400) . "'");
                        echo '<p>' . _t('All messages older than 1 day were deleted') . '</p>';
                        break;

                    case '2':
                        // Проводим полную очистку
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm'");
                        echo '<p>' . _t('Full clearing is finished') . '</p>';
                        break;
                    default :
                        // Чистим сообщения, старше 1 недели
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm' AND `time`<='" . (time() - 604800) . "';");
                        echo '<p>' . _t('All messages older than 1 week were deleted') . '</p>';
                }

                $db->query("OPTIMIZE TABLE `guest`");
                echo '<p><a href="index.php">' . _t('Guestbook') . '</a></p>';
            } else {
                // Запрос параметров очистки
                echo '<div class="phdr"><a href="index.php"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Clear') . '</div>' .
                    '<div class="menu">' .
                    '<form id="clean" method="post" action="index.php?act=clean">' .
                    '<p><h3>' . _t('Clearing parameters') . '</h3>' .
                    '<input type="radio" name="cl" value="0" checked="checked" />' . _t('Older than 1 week') . '<br />' .
                    '<input type="radio" name="cl" value="1" />' . _t('Older than 1 day') . '<br />' .
                    '<input type="radio" name="cl" value="2" />' . _t('Clear all') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Clear') . '" /></p>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . _t('Cancel') . '</a></div>';
            }
        }
        break;

    case 'ga':
        // Переключение режима работы Гостевая / Админ-клуб
        if ($rights >= 1) {
            if (isset($_GET['do']) && $_GET['do'] == 'set') {
                $_SESSION['ga'] = 1;
            } else {
                unset($_SESSION['ga']);
            }
        }

    default:
        // Отображаем Гостевую, или Админ клуб
        if (!$config['mod_guest']) {
            echo '<div class="alarm">' . _t('The guestbook is closed') . '</div>';
        }

        echo '<div class="phdr"><b>' . _t('Guestbook') . '</b></div>';

        if ($rights > 0) {
            $menu = [];
            $menu[] = isset($_SESSION['ga']) ? '<a href="index.php?act=ga">' . _t('Guestbook') . '</a>' : '<b>' . _t('Guestbook') . '</b>';
            $menu[] = isset($_SESSION['ga']) ? '<b>' . _t('Admin Club') . '</b>' : '<a href="index.php?act=ga&amp;do=set">' . _t('Admin Club') . '</a>';
            if ($rights >= 7) {
                $menu[] = '<a href="index.php?act=clean">' . _t('Clear') . '</a>';
            }
            echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        }

        // Форма ввода нового сообщения
        if (($user_id || $config['mod_guest'] == 2) && !isset($ban['1']) && !isset($ban['13'])) {
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '<div class="gmenu"><form name="form" action="index.php?act=say" method="post">';

            if (!$user_id) {
                echo _t('Name') . ' (max 25):<br><input type="text" name="name" maxlength="25"/><br>';
            }

            echo '<b>' . _t('Message') . '</b> <small>(max 5000)</small>:<br>';
            echo bbcode::auto_bb('form', 'msg');
            echo '<textarea rows="' . $set_user['field_h'] . '" name="msg"></textarea><br>';

            if (!$user_id) {
                // CAPTCHA для гостей
                echo '<img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . _t('Symbols on the picture') . '"/><br />' .
                    '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . _t('Symbols on the picture') . '<br />';
            }
            echo '<input type="hidden" name="token" value="' . $token . '"/>' .
                '<input type="submit" name="submit" value="' . _t('Sent') . '"/></form></div>';
        } else {
            echo '<div class="rmenu">' . _t('For registered users only') . '</div>';
        }

        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `adm`='" . (isset($_SESSION['ga']) ? 1 : 0) . "'")->fetchColumn();
        echo '<div class="phdr"><b>' . _t('Comments') . '</b></div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?', $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            if (isset($_SESSION['ga']) && $rights >= "1") {
                // Запрос для Админ клуба
                echo '<div class="rmenu"><b>АДМИН-КЛУБ</b></div>';
                $req = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
            } else {
                // Запрос для обычной Гастивухи
                $req = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='0' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
            }

            for ($i = 0; $res = $req->fetch(); ++$i) {
                $text = '';
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

                if (!$res['id']) {
                    // Запрос по гостям
                    $res_g = $db->query("SELECT `lastdate` FROM `cms_sessions` WHERE `session_id` = '" . md5($res['ip'] . $res['browser']) . "' LIMIT 1")->fetch();
                    $res['lastdate'] = $res_g['lastdate'];
                }

                // Время создания поста
                $text = ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';

                if ($res['user_id']) {
                    // Для зарегистрированных показываем ссылки и смайлы
                    $post = functions::checkout($res['text'], 1, 1);
                    if ($set_user['smileys']) {
                        $post = functions::smileys($post, $res['rights'] >= 1 ? 1 : 0);
                    }
                } else {
                    // Для гостей обрабатываем имя и фильтруем ссылки
                    $res['name'] = functions::checkout($res['name']);
                    $post = functions::antilink(functions::checkout($res['text'], 0, 2));
                }

                if ($res['edit_count']) {
                    // Если пост редактировался, показываем кем и когда
                    $post .= '<br /><span class="gray"><small>Изм. <b>' . $res['edit_who'] . '</b> (' . functions::display_date($res['edit_time']) . ') <b>[' . $res['edit_count'] . ']</b></small></span>';
                }

                if (!empty($res['otvet'])) {
                    // Ответ Администрации
                    $otvet = functions::checkout($res['otvet'], 1, 1);
                    if ($set_user['smileys']) {
                        $otvet = functions::smileys($otvet, 1);
                    }
                    $post .= '<div class="reply"><b>' . $res['admin'] . '</b>: (' . functions::display_date($res['otime']) . ')<br>' . $otvet . '</div>';
                }

                if ($rights >= 6) {
                    $subtext = '<a href="index.php?act=otvet&amp;id=' . $res['gid'] . '">' . _t('Reply') . '</a>' .
                        ($rights >= $res['rights'] ? ' | <a href="index.php?act=edit&amp;id=' . $res['gid'] . '">' . _t('Edit') . '</a> | <a href="index.php?act=delpost&amp;id=' . $res['gid'] . '">' . _t('Delete') . '</a>' : '');
                } else {
                    $subtext = '';
                }

                $arg = [
                    'header' => $text,
                    'body'   => $post,
                    'sub'    => $subtext,
                ];

                echo functions::display_user($res, $arg);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . _t('The guestbook is empty.<br><strong>Be the first! :)</strong>') . '</p></div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?', $start, $total, $kmess) . '</div>' .
                '<p><form action="index.php" method="get"><input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
        }

        break;
}

require('../incfiles/end.php');
