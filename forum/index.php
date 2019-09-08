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

require('../system/bootstrap.php');

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

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

/** @var Johncms\Counters $counters */
$counters = App::getContainer()->get('counters');

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Настройки форума
$set_forum = $systemUser->isValid() ? unserialize($systemUser->set_forum) : [
    'farea'    => 0,
    'upfp'     => 0,
    'preview'  => 1,
    'postclip' => 1,
    'postcut'  => 2,
];

// Список расширений файлов, разрешенных к выгрузке

// Файлы архивов
$ext_arch = [
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    'apk',
];
// Звуковые файлы
$ext_audio = [
    'mp3',
    'amr',
];
// Файлы документов и тексты
$ext_doc = [
    'txt',
    'pdf',
    'doc',
    'docx',
    'rtf',
    'djvu',
    'xls',
    'xlsx',
];
// Файлы Java
$ext_java = [
    'sis',
    'sisx',
    'apk',
];
// Файлы картинок
$ext_pic = [
    'jpg',
    'jpeg',
    'gif',
    'png',
    'bmp',
];
// Файлы SIS
$ext_sis = [
    'sis',
    'sisx',
];
// Файлы видео
$ext_video = [
    '3gp',
    'avi',
    'flv',
    'mpeg',
    'mp4',
];
// Файлы Windows
$ext_win = [
    'exe',
    'msi',
];
// Другие типы файлов (что не перечислены выше)
$ext_other = ['wmf'];

// Ограничиваем доступ к Форуму
$error = '';

if (!$config->mod_forum && $systemUser->rights < 7) {
    $error = _t('Forum is closed');
} elseif ($config->mod_forum == 1 && !$systemUser->isValid()) {
    $error = _t('For registered users only');
}

if ($error) {
    require('../system/head.php');
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require('../system/end.php');
    exit;
}

$headmod = $id ? 'forum,' . $id : 'forum';

// Заголовки страниц форума
if (empty($id)) {
    $textl = _t('Forum');
} else {
    $res = $db->query("SELECT `text` FROM `forum` WHERE `id`= " . $id)->fetch();
    $hdr = preg_replace('#\[c\](.*?)\[/c\]#si', '', $res['text']);
    $hdr = strtr($hdr, [
        '&laquo;' => '',
        '&raquo;' => '',
        '&quot;'  => '',
        '&amp;'   => '',
        '&lt;'    => '',
        '&gt;'    => '',
        '&#039;'  => '',
    ]);
    $hdr = mb_substr($hdr, 0, 30);
    $hdr = $tools->checkout($hdr, 2, 2);
    $textl = empty($hdr) ? _t('Forum') : $hdr;
}

// Переключаем режимы работы
$mods = [
    'addfile',
    'addvote',
    'close',
    'deltema',
    'delvote',
    'editpost',
    'editvote',
    'file',
    'files',
    'filter',
    'loadtem',
    'massdel',
    'new',
    'nt',
    'per',
    'post',
    'ren',
    'restore',
    'say',
    'tema',
    'users',
    'vip',
    'vote',
    'who',
    'curators',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists('includes/' . $mods[$key] . '.php')) {
    require('includes/' . $mods[$key] . '.php');
} else {
    require('../system/head.php');

    // Если форум закрыт, то для Админов выводим напоминание
    if (!$config->mod_forum) {
        echo '<div class="alarm">' . _t('Forum is closed') . '</div>';
    } elseif ($config->mod_forum == 3) {
        echo '<div class="rmenu">' . _t('Read only') . '</div>';
    }

    if (!$systemUser->isValid()) {
        if (isset($_GET['newup'])) {
            $_SESSION['uppost'] = 1;
        }

        if (isset($_GET['newdown'])) {
            $_SESSION['uppost'] = 0;
        }
    }

    if ($id) {
        // Определяем тип запроса (каталог, или тема)
        $type = $db->query("SELECT * FROM `forum` WHERE `id`= '$id'");

        if (!$type->rowCount()) {
            // Если темы не существует, показываем ошибку
            echo $tools->displayError(_t('Topic has been deleted or does not exists'), '<a href="index.php">' . _t('Forum') . '</a>');
            require('../system/end.php');
            exit;
        }

        $type1 = $type->fetch();

        // Фиксация факта прочтения Топика
        if ($systemUser->isValid() && $type1['type'] == 't') {
            $req_r = $db->query("SELECT * FROM `cms_forum_rdm` WHERE `topic_id` = '$id' AND `user_id` = '" . $systemUser->id . "' LIMIT 1");

            if ($req_r->rowCount()) {
                $res_r = $req_r->fetch();

                if ($type1['time'] > $res_r['time']) {
                    $db->exec("UPDATE `cms_forum_rdm` SET `time` = '" . time() . "' WHERE `topic_id` = '$id' AND `user_id` = '" . $systemUser->id . "' LIMIT 1");
                }
            } else {
                $db->exec("INSERT INTO `cms_forum_rdm` SET `topic_id` = '$id', `user_id` = '" . $systemUser->id . "', `time` = '" . time() . "'");
            }
        }

        // Получаем структуру форума
        $res = true;
        $allow = 0;
        $parent = $type1['refid'];

        while ($parent != '0' && $res != false) {
            $res = $db->query("SELECT * FROM `forum` WHERE `id` = '$parent' LIMIT 1")->fetch();

            if ($res['type'] == 'f' || $res['type'] == 'r') {
                $tree[] = '<a href="index.php?id=' . $parent . '">' . $res['text'] . '</a>';

                if ($res['type'] == 'r' && !empty($res['edit'])) {
                    $allow = intval($res['edit']);
                }
            }
            $parent = $res['refid'];
        }

        $tree[] = '<a href="index.php">' . _t('Forum') . '</a>';
        krsort($tree);

        if ($type1['type'] != 't' && $type1['type'] != 'm') {
            $tree[] = '<b>' . $type1['text'] . '</b>';
        }

        // Счетчик файлов и ссылка на них
        $sql = ($systemUser->rights == 9) ? "" : " AND `del` != '1'";

        if ($type1['type'] == 'f') {
            $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `cat` = '$id'" . $sql)->fetchColumn();

            if ($count > 0) {
                $filelink = '<a href="index.php?act=files&amp;c=' . $id . '">' . _t('Category Files') . '</a>';
            }
        } elseif ($type1['type'] == 'r') {
            $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `subcat` = '$id'" . $sql)->fetchColumn();

            if ($count > 0) {
                $filelink = '<a href="index.php?act=files&amp;s=' . $id . '">' . _t('Section Files') . '</a>';
            }
        } elseif ($type1['type'] == 't') {
            $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `topic` = '$id'" . $sql)->fetchColumn();

            if ($count > 0) {
                $filelink = '<a href="index.php?act=files&amp;t=' . $id . '">' . _t('Topic Files') . '</a>';
            }
        }

        $filelink = isset($filelink) ? $filelink . '&#160;<span class="red">(' . $count . ')</span>' : false;

        // Счетчик "Кто в теме?"
        $wholink = false;

        if ($systemUser->isValid() && $type1['type'] == 't') {
            $online_u = $db->query("SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 300) . " AND `place` = 'forum,$id'")->fetchColumn();
            $online_g = $db->query("SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` = 'forum,$id'")->fetchColumn();
            $wholink = '<a href="index.php?act=who&amp;id=' . $id . '">' . _t('Who is here') . '?</a>&#160;<span class="red">(' . $online_u . '&#160;/&#160;' . $online_g . ')</span><br>';
        }

        // Выводим верхнюю панель навигации
        echo '<a id="up"></a><p>' . $counters->forumNew(1) . '</p>' .
            '<div class="phdr">' . implode(' / ', $tree) . '</div>' .
            '<div class="topmenu"><a href="search.php?id=' . $id . '">' . _t('Search') . '</a>' . ($filelink ? ' | ' . $filelink : '') . ($wholink ? ' | ' . $wholink : '') . '</div>';

        switch ($type1['type']) {
            case 'f':
                ////////////////////////////////////////////////////////////
                // Список разделов форума                                 //
                ////////////////////////////////////////////////////////////
                $req = $db->query("SELECT `id`, `text`, `soft`, `edit` FROM `forum` WHERE `type`='r' AND `refid`='$id' ORDER BY `realid`");
                $total = $req->rowCount();

                if ($total) {
                    $i = 0;

                    while ($res = $req->fetch()) {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        $coltem = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '" . $res['id'] . "'")->fetchColumn();
                        echo '<a href="?id=' . $res['id'] . '">' . $res['text'] . '</a>';

                        if ($coltem) {
                            echo " [$coltem]";
                        }

                        if (!empty($res['soft'])) {
                            echo '<div class="sub"><span class="gray">' . $res['soft'] . '</span></div>';
                        }

                        echo '</div>';
                        ++$i;
                    }

                    unset($_SESSION['fsort_id']);
                    unset($_SESSION['fsort_users']);
                } else {
                    echo '<div class="menu"><p>' . _t('There are no sections in this category') . '</p></div>';
                }

                echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
                break;

            case 'r':
                ////////////////////////////////////////////////////////////
                // Список топиков                                         //
                ////////////////////////////////////////////////////////////
                $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `refid`='$id'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'"))->fetchColumn();

                if (($systemUser->isValid() && !isset($systemUser->ban['1']) && !isset($systemUser->ban['11']) && $config->mod_forum != 4) || $systemUser->rights) {
                    // Кнопка создания новой темы
                    echo '<div class="gmenu"><form action="index.php?act=nt&amp;id=' . $id . '" method="post"><input type="submit" value="' . _t('New Topic') . '" /></form></div>';
                }

                if ($total) {
                    $req = $db->query("SELECT * FROM `forum` WHERE `type`='t'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'") . " AND `refid`='$id' ORDER BY `vip` DESC, `time` DESC LIMIT $start, $kmess");
                    $i = 0;

                    while ($res = $req->fetch()) {
                        if ($res['close']) {
                            echo '<div class="rmenu">';
                        } else {
                            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        }

                        $nam = $db->query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $res['id'] . "' ORDER BY `time` DESC LIMIT 1")->fetch();
                        $colmes = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='m' AND `refid`='" . $res['id'] . "'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn();
                        $cpg = ceil($colmes / $kmess);
                        $np = $db->query("SELECT COUNT(*) FROM `cms_forum_rdm` WHERE `time` >= '" . $res['time'] . "' AND `topic_id` = '" . $res['id'] . "' AND `user_id` = " . $systemUser->id)->fetchColumn();
                        // Значки
                        $icons = [
                            ($np ? (!$res['vip'] ? $tools->image('op.gif') : '') : $tools->image('np.gif')),
                            ($res['vip'] ? $tools->image('pt.gif') : ''),
                            ($res['realid'] ? $tools->image('rate.gif') : ''),
                            ($res['edit'] ? $tools->image('tz.gif') : ''),
                        ];
                        echo implode('', array_filter($icons));
                        echo '<a href="index.php?id=' . $res['id'] . '">' . (empty($res['text']) ? '-----' : $res['text']) . '</a> [' . $colmes . ']';

                        if ($cpg > 1) {
                            echo '<a href="index.php?id=' . $res['id'] . '&amp;page=' . $cpg . '">&#160;&gt;&gt;</a>';
                        }

                        echo '<div class="sub">';
                        echo $res['from'];

                        if (!empty($nam['from'])) {
                            echo '&#160;/&#160;' . $nam['from'];
                        }

                        echo ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span></div></div>';
                        ++$i;
                    }
                    unset($_SESSION['fsort_id']);
                    unset($_SESSION['fsort_users']);
                } else {
                    echo '<div class="menu"><p>' . _t('No topics in this section') . '</p></div>';
                }

                echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

                if ($total > $kmess) {
                    echo '<div class="topmenu">' . $tools->displayPagination('index.php?id=' . $id . '&amp;', $start, $total, $kmess) . '</div>' .
                        '<p><form action="index.php?id=' . $id . '" method="post">' .
                        '<input type="text" name="page" size="2"/>' .
                        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                        '</form></p>';
                }
                break;

            case 't':
                ////////////////////////////////////////////////////////////
                // Показываем тему с постами                              //
                ////////////////////////////////////////////////////////////
                $filter = isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id ? 1 : 0;
                $sql = '';

                if ($filter && !empty($_SESSION['fsort_users'])) {
                    // Подготавливаем запрос на фильтрацию юзеров
                    $sw = 0;
                    $sql = ' AND (';
                    $fsort_users = unserialize($_SESSION['fsort_users']);

                    foreach ($fsort_users as $val) {
                        if ($sw) {
                            $sql .= ' OR ';
                        }

                        $sortid = intval($val);
                        $sql .= "`forum`.`user_id` = '$sortid'";
                        $sw = 1;
                    }
                    $sql .= ')';
                }

                // Если тема помечена для удаления, разрешаем доступ только администрации
                if ($systemUser->rights < 6 && $type1['close'] == 1) {
                    echo '<div class="rmenu"><p>' . _t('Topic deleted') . '<br><a href="?id=' . $type1['refid'] . '">' . _t('Go to Section') . '</a></p></div>';
                    require('../system/end.php');
                    exit;
                }

                // Счетчик постов темы
                $colmes = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='m'$sql AND `refid`='$id'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn();

                if ($start >= $colmes) {
                    // Исправляем запрос на несуществующую страницу
                    $start = max(0, $colmes - (($colmes % $kmess) == 0 ? $kmess : ($colmes % $kmess)));
                }

                // Выводим название топика
                echo '<div class="phdr"><a href="#down">' . $tools->image('down.png', ['class' => '']) . '</a>&#160;&#160;<b>' . (empty($type1['text']) ? '-----' : $type1['text']) . '</b></div>';

                if ($colmes > $kmess) {
                    echo '<div class="topmenu">' . $tools->displayPagination('index.php?id=' . $id . '&amp;', $start, $colmes, $kmess) . '</div>';
                }

                // Метка удаления темы
                if ($type1['close']) {
                    echo '<div class="rmenu">' . _t('Topic deleted by') . ': <b>' . $type1['close_who'] . '</b></div>';
                } elseif (!empty($type1['close_who']) && $systemUser->rights >= 7) {
                    echo '<div class="gmenu"><small>' . _t('Undelete topic') . ': <b>' . $type1['close_who'] . '</b></small></div>';
                }

                // Метка закрытия темы
                if ($type1['edit']) {
                    echo '<div class="rmenu">' . _t('Topic closed') . '</div>';
                }

                // Блок голосований
                if ($type1['realid']) {
                    $clip_forum = isset($_GET['clip']) ? '&amp;clip' : '';
                    $vote_user = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `user`='" . $systemUser->id . "' AND `topic`='$id'")->fetchColumn();
                    $topic_vote = $db->query("SELECT `name`, `time`, `count` FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='$id' LIMIT 1")->fetch();
                    echo '<div  class="gmenu"><b>' . $tools->checkout($topic_vote['name']) . '</b><br />';
                    $vote_result = $db->query("SELECT `id`, `name`, `count` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "' ORDER BY `id` ASC");

                    if (!$type1['edit'] && !isset($_GET['vote_result']) && $systemUser->isValid() && $vote_user == 0) {
                        // Выводим форму с опросами
                        echo '<form action="index.php?act=vote&amp;id=' . $id . '" method="post">';

                        while ($vote = $vote_result->fetch()) {
                            echo '<input type="radio" value="' . $vote['id'] . '" name="vote"/> ' . $tools->checkout($vote['name'], 0, 1) . '<br />';
                        }

                        echo '<p><input type="submit" name="submit" value="' . _t('Vote') . '"/><br /><a href="index.php?id=' . $id . '&amp;start=' . $start . '&amp;vote_result' . $clip_forum .
                            '">' . _t('Results') . '</a></p></form></div>';
                    } else {
                        // Выводим результаты голосования
                        echo '<small>';

                        while ($vote = $vote_result->fetch()) {
                            $count_vote = $topic_vote['count'] ? round(100 / $topic_vote['count'] * $vote['count']) : 0;
                            echo $tools->checkout($vote['name'], 0, 1) . ' [' . $vote['count'] . ']<br />';
                            echo '<img src="vote_img.php?img=' . $count_vote . '" alt="' . _t('Rating') . ': ' . $count_vote . '%" /><br />';
                        }

                        echo '</small></div><div class="bmenu">' . _t('Total votes') . ': ';

                        if ($systemUser->rights > 6) {
                            echo '<a href="index.php?act=users&amp;id=' . $id . '">' . $topic_vote['count'] . '</a>';
                        } else {
                            echo $topic_vote['count'];
                        }

                        echo '</div>';

                        if ($systemUser->isValid() && $vote_user == 0) {
                            echo '<div class="bmenu"><a href="index.php?id=' . $id . '&amp;start=' . $start . $clip_forum . '">' . _t('Vote') . '</a></div>';
                        }
                    }
                }

                // Получаем данные о кураторах темы
                $curators = !empty($type1['curators']) ? unserialize($type1['curators']) : [];
                $curator = false;

                if ($systemUser->rights < 6 && $systemUser->rights != 3 && $systemUser->isValid()) {
                    if (array_key_exists($systemUser->id, $curators)) {
                        $curator = true;
                    }
                }

                // Фиксация первого поста в теме
                if (($set_forum['postclip'] == 2 && ($set_forum['upfp'] ? $start < (ceil($colmes - $kmess)) : $start > 0)) || isset($_GET['clip'])) {
                    $postres = $db->query("SELECT `forum`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
                    FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
                    WHERE `forum`.`type` = 'm' AND `forum`.`refid` = '$id'" . ($systemUser->rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
                    ORDER BY `forum`.`id` LIMIT 1")->fetch();
                    echo '<div class="topmenu"><p>';

                    if ($systemUser->isValid() && $systemUser->id != $postres['user_id']) {
                        echo '<a href="../profile/?user=' . $postres['user_id'] . '&amp;fid=' . $postres['id'] . '"><b>' . $postres['from'] . '</b></a> ' .
                            '<a href="index.php?act=say&amp;id=' . $postres['id'] . '&amp;start=' . $start . '"> ' . _t('[r]') . '</a> ' .
                            '<a href="index.php?act=say&amp;id=' . $postres['id'] . '&amp;start=' . $start . '&amp;cyt"> ' . _t('[q]') . '</a> ';
                    } else {
                        echo '<b>' . $postres['from'] . '</b> ';
                    }

                    $user_rights = [
                        3 => '(FMod)',
                        6 => '(Smd)',
                        7 => '(Adm)',
                        9 => '(SV!)',
                    ];
                    echo @$user_rights[$postres['rights']];
                    echo(time() > $postres['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');
                    echo ' <span class="gray">(' . $tools->displayDate($postres['time']) . ')</span><br>';

                    if ($postres['close']) {
                        echo '<span class="red">' . _t('Post deleted') . '</span><br>';
                    }

                    echo $tools->checkout(mb_substr($postres['text'], 0, 500), 0, 2);

                    if (mb_strlen($postres['text']) > 500) {
                        echo '...<a href="index.php?act=post&amp;id=' . $postres['id'] . '">' . _t('Read more') . '</a>';
                    }

                    echo '</p></div>';
                }

                // Памятка, что включен фильтр
                if ($filter) {
                    echo '<div class="rmenu">' . _t('Filter by author is activated') . '</div>';
                }

                // Задаем правила сортировки (новые внизу / вверху)
                if ($systemUser->isValid()) {
                    $order = $set_forum['upfp'] ? 'DESC' : 'ASC';
                } else {
                    $order = ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0)) ? 'ASC' : 'DESC';
                }

                ////////////////////////////////////////////////////////////
                // Основной запрос в базу, получаем список постов темы    //
                ////////////////////////////////////////////////////////////
                $req = $db->query("
                  SELECT `forum`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
                  FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
                  WHERE `forum`.`type` = 'm' AND `forum`.`refid` = '$id'"
                    . ($systemUser->rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "$sql
                  ORDER BY `forum`.`id` $order LIMIT $start, $kmess
                ");

                // Верхнее поле "Написать"
                if (($systemUser->isValid() && !$type1['edit'] && $set_forum['upfp'] && $config->mod_forum != 3 && $allow != 4) || ($systemUser->rights >= 7 && $set_forum['upfp'])) {
                    echo '<div class="gmenu"><form name="form1" action="index.php?act=say&amp;id=' . $id . '" method="post">';

                    if ($set_forum['farea']) {
                        $token = mt_rand(1000, 100000);
                        $_SESSION['token'] = $token;
                        echo '<p>' .
                            $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form1', 'msg') .
                            '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="msg"></textarea></p>' .
                            '<p><input type="checkbox" name="addfiles" value="1" /> ' . _t('Add File') .
                            '</p><p><input type="submit" name="submit" value="' . _t('Write') . '" style="width: 107px; cursor: pointer;"/> ' .
                            (isset($set_forum['preview']) && $set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
                            '<input type="hidden" name="token" value="' . $token . '"/>' .
                            '</p></form></div>';
                    } else {
                        echo '<p><input type="submit" name="submit" value="' . _t('Write') . '"/></p></form></div>';
                    }
                }

                // Для администрации включаем форму массового удаления постов
                if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
                    echo '<form action="index.php?act=massdel" method="post">';
                }
                $i = 1;

                ////////////////////////////////////////////////////////////
                // Основной список постов                                 //
                ////////////////////////////////////////////////////////////
                while ($res = $req->fetch()) {
                    // Фон поста
                    if ($res['close']) {
                        echo '<div class="rmenu">';
                    } else {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    }

                    // Пользовательский аватар
                    echo '<table cellpadding="0" cellspacing="0"><tr><td>';

                    if (file_exists(('../files/users/avatar/' . $res['user_id'] . '.png'))) {
                        echo '<img src="../files/users/avatar/' . $res['user_id'] . '.png" width="32" height="32" alt="' . $res['from'] . '" />&#160;';
                    } else {
                        echo '<img src="../images/empty.png" width="32" height="32" alt="' . $res['from'] . '" />&#160;';
                    }
                    echo '</td><td>';

                    // Метка пола
                    if ($res['sex']) {
                        echo $tools->image(($res['sex'] == 'm' ? 'm' : 'w') . ($res['datereg'] > time() - 86400 ? '_new' : '') . '.png', ['class' => 'icon-inline']);
                    } else {
                        echo $tools->image('del.png');
                    }

                    // Ник юзера и ссылка на его анкету
                    if ($systemUser->isValid() && $systemUser->id != $res['user_id']) {
                        echo '<a href="../profile/?user=' . $res['user_id'] . '"><b>' . $res['from'] . '</b></a> ';
                    } else {
                        echo '<b>' . $res['from'] . '</b> ';
                    }

                    // Метка должности
                    $user_rights = [
                        3 => '(FMod)',
                        6 => '(Smd)',
                        7 => '(Adm)',
                        9 => '(SV!)',
                    ];
                    echo(isset($user_rights[$res['rights']]) ? $user_rights[$res['rights']] : '');

                    // Метка онлайн/офлайн
                    echo(time() > $res['lastdate'] + 300 ? '<span class="red"> [Off]</span> ' : '<span class="green"> [ON]</span> ');

                    // Ссылка на пост
                    echo '<a href="index.php?act=post&amp;id=' . $res['id'] . '" title="Link to post">[#]</a>';

                    // Ссылки на ответ и цитирование
                    if ($systemUser->isValid() && $systemUser->id != $res['user_id']) {
                        echo '&#160;<a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '">' . _t('[r]') . '</a>&#160;' .
                            '<a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt">' . _t('[q]') . '</a> ';
                    }

                    // Время поста
                    echo ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span><br />';

                    // Статус пользователя
                    if (!empty($res['status'])) {
                        echo '<div class="status">' . $tools->image('label.png', ['class' => 'icon-inline']) . $res['status'] . '</div>';
                    }

                    // Закрываем таблицу с аватаром
                    echo '</td></tr></table>';

                    ////////////////////////////////////////////////////////////
                    // Вывод текста поста                                     //
                    ////////////////////////////////////////////////////////////
                    $text = $res['text'];
                    $text = $tools->checkout($text, 1, 1);
                    $text = $tools->smilies($text, $res['rights'] ? 1 : 0);
                    echo $text;

                    // Если пост редактировался, показываем кем и когда
                    if ($res['kedit']) {
                        echo '<br /><span class="gray"><small>' . _t('Edited') . ' <b>' . $res['edit'] . '</b> (' . $tools->displayDate($res['tedit']) . ') <b>[' . $res['kedit'] . ']</b></small></span>';
                    }

                    // Задаем права на редактирование постов
                    if (
                        (($systemUser->rights == 3 || $systemUser->rights >= 6 || $curator) && $systemUser->rights >= $res['rights'])
                        || ($res['user_id'] == $systemUser->id && !$set_forum['upfp'] && ($start + $i) == $colmes && $res['time'] > time() - 300)
                        || ($res['user_id'] == $systemUser->id && $set_forum['upfp'] && $start == 0 && $i == 1 && $res['time'] > time() - 300)
                        || ($i == 1 && $allow == 2 && $res['user_id'] == $systemUser->id)
                    ) {
                        $allowEdit = true;
                    } else {
                        $allowEdit = false;
                    }

                    // Если есть прикрепленные файлы, выводим их
                    $freq = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");

                    if ($freq->rowCount()) {
                        echo '<div class="post-files">';
                        while ($fres = $freq->fetch()) {
                            $fls = round(@filesize('../files/forum/attach/' . $fres['filename']) / 1024, 2);
                            echo '<div class="gray" style="font-size: x-small;background-color: rgba(128, 128, 128, 0.1);padding: 2px 4px;float: left;margin: 4px 4px 0 0;">' . _t('Attachment') . ':';
                            // Предпросмотр изображений
                            $att_ext = strtolower(pathinfo('./files/forum/attach/' . $fres['filename'], PATHINFO_EXTENSION));
                            $pic_ext = [
                                'gif',
                                'jpg',
                                'jpeg',
                                'png',
                            ];

                            if (in_array($att_ext, $pic_ext)) {
                                echo '<div><a href="index.php?act=file&amp;id=' . $fres['id'] . '">';
                                echo '<img src="thumbinal.php?file=' . (urlencode($fres['filename'])) . '" alt="' . _t('Click to view image') . '" /></a></div>';
                            } else {
                                echo '<br><a href="index.php?act=file&amp;id=' . $fres['id'] . '">' . $fres['filename'] . '</a>';
                            }

                            echo ' (' . $fls . ' кб.)<br>';
                            echo _t('Downloads') . ': ' . $fres['dlcount'] . ' ' . _t('Time');

                            if ($allowEdit) {
                                echo '<br><a href="?act=editpost&amp;do=delfile&amp;fid=' . $fres['id'] . '&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>';
                            }

                            echo '</div>';
                            $file_id = $fres['id'];
                        }
                        echo '<div style="clear: both;"></div></div>';
                    }

                    // Ссылки на редактирование / удаление постов
                    if ($allowEdit) {
                        echo '<div class="sub">';

                        // Чекбокс массового удаления постов
                        if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
                            echo '<input type="checkbox" name="delch[]" value="' . $res['id'] . '"/>&#160;';
                        }

                        // Служебное меню поста
                        $menu = [
                            '<a href="index.php?act=editpost&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a>',
                            ($systemUser->rights >= 7 && $res['close'] == 1 ? '<a href="index.php?act=editpost&amp;do=restore&amp;id=' . $res['id'] . '">' . _t('Restore') . '</a>' : ''),
                            ($res['close'] == 1 ? '' : '<a href="index.php?act=editpost&amp;do=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>'),
                        ];
                        echo implode(' | ', array_filter($menu));

                        // Показываем, кто удалил пост
                        if ($res['close']) {
                            echo '<div class="red">' . _t('Post deleted') . ': <b>' . $res['close_who'] . '</b></div>';
                        } elseif (!empty($res['close_who'])) {
                            echo '<div class="green">' . _t('Post restored by') . ': <b>' . $res['close_who'] . '</b></div>';
                        }

                        // Показываем IP и Useragent
                        if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
                            if ($res['ip_via_proxy']) {
                                echo '<div class="gray"><b class="red"><a href="' . $config->homeurl . '/admin/index.php?act=search_ip&amp;ip=' . long2ip($res['ip']) . '">' . long2ip($res['ip']) . '</a></b> - ' .
                                    '<a href="' . $config->homeurl . '/admin/index.php?act=search_ip&amp;ip=' . long2ip($res['ip_via_proxy']) . '">' . long2ip($res['ip_via_proxy']) . '</a>' .
                                    ' - ' . $res['soft'] . '</div>';
                            } else {
                                echo '<div class="gray"><a href="' . $config->homeurl . '/admin/index.php?act=search_ip&amp;ip=' . long2ip($res['ip']) . '">' . long2ip($res['ip']) . '</a> - ' . $res['soft'] . '</div>';
                            }
                        }

                        echo '</div>';
                    }

                    echo '</div>';
                    ++$i;
                }

                // Кнопка массового удаления постов
                if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
                    echo '<div class="rmenu"><input type="submit" value=" ' . _t('Delete') . ' "/></div>';
                    echo '</form>';
                }

                // Нижнее поле "Написать"
                if (($systemUser->isValid() && !$type1['edit'] && !$set_forum['upfp'] && $config->mod_forum != 3 && $allow != 4) || ($systemUser->rights >= 7 && !$set_forum['upfp'])) {
                    echo '<div class="gmenu"><form name="form2" action="index.php?act=say&amp;id=' . $id . '" method="post">';

                    if ($set_forum['farea']) {
                        $token = mt_rand(1000, 100000);
                        $_SESSION['token'] = $token;
                        echo '<p>';
                        echo $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form2', 'msg');
                        echo '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="msg"></textarea><br></p>' .
                            '<p><input type="checkbox" name="addfiles" value="1" /> ' . _t('Add File');

                        echo '</p><p><input type="submit" name="submit" value="' . _t('Write') . '" style="width: 107px; cursor: pointer;"/> ' .
                            (isset($set_forum['preview']) && $set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
                            '<input type="hidden" name="token" value="' . $token . '"/>' .
                            '</p></form></div>';
                    } else {
                        echo '<p><input type="submit" name="submit" value="' . _t('Write') . '"/></p></form></div>';
                    }
                }

                echo '<div class="phdr"><a id="down"></a><a href="#up">' . $tools->image('up.png', ['class' => '']) . '</a>' .
                    '&#160;&#160;' . _t('Total') . ': ' . $colmes . '</div>';

                // Постраничная навигация
                if ($colmes > $kmess) {
                    echo '<div class="topmenu">' . $tools->displayPagination('index.php?id=' . $id . '&amp;', $start, $colmes, $kmess) . '</div>' .
                        '<p><form action="index.php?id=' . $id . '" method="post">' .
                        '<input type="text" name="page" size="2"/>' .
                        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                        '</form></p>';
                } else {
                    echo '<br />';
                }

                // Список кураторов
                if ($curators) {
                    $array = [];

                    foreach ($curators as $key => $value) {
                        $array[] = '<a href="../profile/?user=' . $key . '">' . $value . '</a>';
                    }

                    echo '<p><div class="func">' . _t('Curators') . ': ' . implode(', ', $array) . '</div></p>';
                }

                // Ссылки на модерские функции управления темой
                if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
                    echo '<p><div class="func">';

                    if ($systemUser->rights >= 7) {
                        echo '<a href="index.php?act=curators&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Curators of the Topic') . '</a><br />';
                    }

                    echo isset($topic_vote) && $topic_vote > 0
                        ? '<a href="index.php?act=editvote&amp;id=' . $id . '">' . _t('Edit Poll') . '</a><br><a href="index.php?act=delvote&amp;id=' . $id . '">' . _t('Delete Poll') . '</a><br>'
                        : '<a href="index.php?act=addvote&amp;id=' . $id . '">' . _t('Add Poll') . '</a><br>';
                    echo '<a href="index.php?act=ren&amp;id=' . $id . '">' . _t('Rename Topic') . '</a><br>';

                    // Закрыть - открыть тему
                    if ($type1['edit'] == 1) {
                        echo '<a href="index.php?act=close&amp;id=' . $id . '">' . _t('Open Topic') . '</a><br>';
                    } else {
                        echo '<a href="index.php?act=close&amp;id=' . $id . '&amp;closed">' . _t('Close Topic') . '</a><br>';
                    }

                    // Удалить - восстановить тему
                    if ($type1['close'] == 1) {
                        echo '<a href="index.php?act=restore&amp;id=' . $id . '">' . _t('Restore Topic') . '</a><br>';
                    }

                    echo '<a href="index.php?act=deltema&amp;id=' . $id . '">' . _t('Delete Topic') . '</a><br>';

                    if ($type1['vip'] == 1) {
                        echo '<a href="index.php?act=vip&amp;id=' . $id . '">' . _t('Unfix Topic') . '</a>';
                    } else {
                        echo '<a href="index.php?act=vip&amp;id=' . $id . '&amp;vip">' . _t('Pin Topic') . '</a>';
                    }

                    echo '<br><a href="index.php?act=per&amp;id=' . $id . '">' . _t('Move Topic') . '</a></div></p>';
                }

                // Ссылка на список "Кто в теме"
                if ($wholink) {
                    echo '<div>' . $wholink . '</div>';
                }

                // Ссылка на фильтр постов
                if ($filter) {
                    echo '<div><a href="index.php?act=filter&amp;id=' . $id . '&amp;do=unset">' . _t('Cancel Filter') . '</a></div>';
                } else {
                    echo '<div><a href="index.php?act=filter&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Filter by author') . '</a></div>';
                }

                // Ссылка на скачку темы
                echo '<a href="index.php?act=tema&amp;id=' . $id . '">' . _t('Download Topic') . '</a>';
                break;

            default:
                // Если неверные данные, показываем ошибку
                echo $tools->displayError(_t('Wrong data'));
                break;
        }
    } else {
        ////////////////////////////////////////////////////////////
        // Список Категорий форума                                //
        ////////////////////////////////////////////////////////////
        $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files`" . ($systemUser->rights >= 7 ? '' : " WHERE `del` != '1'"))->fetchColumn();
        echo '<p>' . $counters->forumNew(1) . '</p>' .
            '<div class="phdr"><b>' . _t('Forum') . '</b></div>' .
            '<div class="topmenu"><a href="search.php">' . _t('Search') . '</a> | <a href="index.php?act=files">' . _t('Files') . '</a> <span class="red">(' . $count . ')</span></div>';
        $req = $db->query("SELECT `id`, `text`, `soft` FROM `forum` WHERE `type`='f' ORDER BY `realid`");
        $i = 0;

        while ($res = $req->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $count = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='r' AND `refid`='" . $res['id'] . "'")->fetchColumn();
            echo '<a href="index.php?id=' . $res['id'] . '">' . $res['text'] . '</a> [' . $count . ']';

            if (!empty($res['soft'])) {
                echo '<div class="sub"><span class="gray">' . $res['soft'] . '</span></div>';
            }

            echo '</div>';
            ++$i;
        }
        $online_u = $db->query("SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE 'forum%'")->fetchColumn();
        $online_g = $db->query("SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE 'forum%'")->fetchColumn();
        echo '<div class="phdr">' . ($systemUser->isValid() ? '<a href="index.php?act=who">' . _t('Who in Forum') . '</a>' : _t('Who in Forum')) . '&#160;(' . $online_u . '&#160;/&#160;' . $online_g . ')</div>';
        unset($_SESSION['fsort_id']);
        unset($_SESSION['fsort_users']);
    }

    // Навигация внизу страницы
    echo '<p>' . ($id ? '<a href="index.php">' . _t('Forum') . '</a><br />' : '');

    if (!$id) {
        echo '<a href="../help/?act=forum">' . _t('Forum rules') . '</a>';
    }

    echo '</p>';

    if (!$systemUser->isValid()) {
        if ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0)) {
            echo '<a href="index.php?id=' . $id . '&amp;page=' . $page . '&amp;newup">' . _t('New at the top') . '</a>';
        } else {
            echo '<a href="index.php?id=' . $id . '&amp;page=' . $page . '&amp;newdown">' . _t('New at the bottom') . '</a>';
        }
    }
}

require_once('../system/end.php');
