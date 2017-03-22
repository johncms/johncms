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
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

$headmod = 'library';
require_once('../system/bootstrap.php');

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

use Library\Tree;
use Library\Hashtags;
use Library\Rating;
use Library\Links;
use Library\Utils;

/*  php 7+
use Library\{
            Tree, 
            Hashtags, 
            Rating, 
            Links
}
*/

$adm = ($systemUser->rights > 4) ? true : false;
$i = 0;

$textl = _t('Library');

// Ограничиваем доступ к Библиотеке

$error = '';

if (!$config['mod_lib'] && $systemUser->rights < 7) {
    $error = _t('Library is closed');
} elseif ($config['mod_lib'] == 1 && !$systemUser->isValid()) {
    $error = _t('Access forbidden');
}

if ($error) {
    require_once('../system/head.php');
    echo $tools->displayError($error);
    require_once('../system/end.php');
    exit;
}

// Заголовки библиотеки
switch ($do) {
    case 'dir':
        $tab = 'library_cats';
        break;

    default:
        $tab = 'library_texts';
}

if ($id > 0) { 
    $hdrsql = $db->query("SELECT `name` FROM `" . $tab . "` WHERE `id`=" . $id . " LIMIT 1");

    $hdrres = '';
    if ($hdrsql->rowCount()) {
        $hdrres = $hdrsql->fetchColumn();
    }

    $hdr = htmlentities($hdrres, ENT_QUOTES, 'UTF-8');
    if ($hdr) {
        $textl .=  ' | ' . (mb_strlen($hdr) > 30 ? $hdr . '...' : $hdr);
    }
}

require_once('../system/head.php');

?>

    <!-- style table image -->
    <style type="text/css">
        .avatar {
            display: table-cell;
            vertical-align: top;
        }

        .avatar img {
            height: 32px;
            margin-right: 5px;
            margin-bottom: 5px;
            width: 32px;
        }

        .righttable {
            display: table-cell;
            vertical-align: top;
            width: 100%;
        }
    </style>
    <!-- end style -->

<?php

if (!$config['mod_lib']) {
    echo $tools->displayError(_t('Library is closed'));
}

$array_includes = [
    'addnew',
    'comments',
    'del',
    'download',
    'mkdir',
    'moder',
    'move',
    'new',
    'premod',
    'search',
    'top',
    'tags',
    'tagcloud',
    'lastcom',
];
$i = 0;

if (in_array($act, $array_includes)) {
    require_once('includes/' . $act . '.php');
} else {
    if (!$id) {
        echo '<div class="phdr"><strong>' . _t('Library') . '</strong></div>';
        echo '<div class="topmenu"><a href="?act=search">' . _t('Search') . '</a> | <a href="?act=tagcloud">' . _t('Tag Cloud') . '</a></div>';
        echo '<div class="gmenu"><p>';

        if ($adm) {
            // Считаем число статей, ожидающих модерацию
            $res = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `premod`=0")->fetchColumn();

            if ($res > 0) {
                echo '<div>' . _t('On moderation') . ': <a href="?act=premod">' . $res . '</a></div>';
            }
        }

        $res = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1")->fetchColumn();

        if ($res) {
            echo $tools->image('new.png', [
                    'width' => 16,
                    'height' => 16
                ]) . '<a href="?act=new">' . _t('New Articles') . '</a> (' . $res . ')<br>';
        }

        echo $tools->image('rate.gif',
                ['width' => 16, 'height' => 16]) . '<a href="?act=top">' . _t('Rating articles') . '</a><br>' .
            $tools->image('talk.gif',
                ['width' => 16, 'height' => 16]) . '<a href="?act=lastcom">' . _t('Latest comments') . '</a>' .
            '</p></div>';

        $total = $db->query("SELECT COUNT(*) FROM `library_cats` WHERE `parent`=0")->fetchColumn();
        $y = 0;

        if ($total) {
            $req = $db->query("SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE `parent`=0 ORDER BY `pos` ASC");

            while ($row = $req->fetch()) {
                $y++;
                echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                    . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> ('
                    . $db->query("SELECT COUNT(*) FROM `" . ($row['dir'] ? 'library_cats' : 'library_texts') . "` WHERE " . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id']))->fetchColumn() . ')';

                if (!empty($row['description'])) {
                    echo '<div style="font-size: x-small; padding-top: 2px"><span class="gray">' . $tools->checkout($row['description']) . '</span></div>';
                }

                if ($adm) {
                    echo '<div class="sub">'
                        . ($y != 1 ? '<a href="?act=move&amp;moveset=up&amp;posid=' . $y . '">' . _t('Up') . '</a> | ' : _t('Up') . ' | ')
                        . ($y != $total ? '<a href="?act=move&amp;moveset=down&amp;posid=' . $y . '">' . _t('Down') . '</a>' : _t('Down'))
                        . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Edit') . '</a>'
                        . ' | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></div>';
                }

                echo '</div>';
            }
        } else {
            echo '<div class="menu">' . _t('The list is empty') . '</div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($adm) {
            echo '<p><a href="?act=mkdir&amp;id=0">' . _t('Create Section') . '</a></p>';
        }
    } else {
        $dir_nav = new Tree($id);
        $dir_nav->processNavPanel();

        switch ($do) {
            case 'dir':
                // dir
                $actdir = $db->query("SELECT `id`, `dir` FROM `library_cats` WHERE "
                    . ($id !== null ? '`id`=' . $id : 1) . " LIMIT 1")->fetch();
                if ($actdir['id'] > 0) {
                    $actdir = $actdir['dir'];
                } else {
                    Utils::redir404();
                }
                echo '<div class="phdr">' . $dir_nav->printNavPanel() . '</div>';

                if ($actdir) {
                    $total = $db->query("SELECT COUNT(*) FROM `library_cats` WHERE "
                        . ($id !== null ? '`parent`=' . $id : '`parent`=0'))->fetchColumn();
                    $nav = ($total > $kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;',
                            $start, $total, $kmess) . '</div>' : '';
                    $y = 0;

                    if ($total) {
                        $sql = $db->query("SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE "
                            . ($id !== null ? '`parent`=' . $id : '`parent`=0') . ' ORDER BY `pos` ASC LIMIT ' . $start . ',' . $kmess);
                        echo $nav;

                        while ($row = $sql->fetch()) {
                            $y++;
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> ('
                                . $db->query("SELECT COUNT(*) FROM `" . ($row['dir'] ? 'library_cats' : 'library_texts') . "` WHERE "
                                    . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id']))->fetchColumn() . ' '
                                . ($row['dir'] ? ' ' . _t('Sections') : ' ' . _t('Articles')) . ')'
                                . '<div class="sub"><span class="gray">' . $tools->checkout($row['description']) . '</span></div>';

                            if ($adm) {
                                echo '<div class="sub">'
                                    . ($y != 1 ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=up&amp;posid=' . $y . '">' . _t('Up')
                                        . '</a> | ' : '' . _t('Up') . ' | ')
                                    . ($y != $total
                                        ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=down&amp;posid=' . $y . '">' . _t('Down') . '</a>'
                                        : _t('Down'))
                                    . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Edit') . '</a>'
                                    . ' | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></div>';
                            }

                            echo '</div>';
                        }
                    } else {
                        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
                    }

                    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
                    echo $nav;

                    if ($adm) {
                        echo '<p><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
                            . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a><br>'
                            . '<a href="?act=mkdir&amp;id=' . $id . '">' . _t('Create') . '</a></p>';
                    }
                } else {
                    $total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod`=1 AND `cat_id`=' . $id)->fetchColumn();
                    $page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
                    $start = $page == 1 ? 0 : ($page - 1) * $kmess;
                    $nav = ($total > $kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;',
                            $start, $total, $kmess) . '</div>' : '';

                    if ($total) {
                        $sql2 = $db->query("SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, `announce` FROM `library_texts` WHERE `premod`=1 AND `cat_id`=" . $id . " ORDER BY `id` DESC LIMIT " . $start . "," . $kmess);
                        echo $nav;

                        while ($row = $sql2->fetch()) {
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
                                    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                                    : '')
                                . '<div class="righttable"><h4><a href="index.php?id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a></h4>'
                                . '<div><small>' . $tools->checkout($row['announce'], 0, 0) . '</small></div></div>';

                            // Описание к статье
                            $obj = new Hashtags($row['id']);
                            $rate = new Rating($row['id']);
                            $uploader = $row['uploader_id']
                                ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . $tools->checkout($row['uploader']) . '</a>'
                                : $tools->checkout($row['uploader']);
                            echo '<table class="desc">'
                                // Тэги
                                . ($obj->getAllStatTags()
                                    ? '<tr><td class="caption">' . _t('The Tags') . ':</td>'
                                    . '<td>' . $obj->getAllStatTags(1) . '</td></tr>'
                                    : '')
                                // Кто добавил?
                                . '<tr>'
                                . '<td class="caption">' . _t('Who added') . ':</td>'
                                . '<td>' . $uploader . ' (' . $tools->displayDate($row['time']) . ')</td>'
                                . '</tr>'
                                // Рейтинг
                                . '<tr>'
                                . '<td class="caption">' . _t('Rating') . ':</td>'
                                . '<td>' . $rate->viewRate() . '</td>'
                                . '</tr>';
                            echo '</table></div>';
                        }
                    } else {
                        echo '<div class="menu">' . _t('The list is empty') . '</div>';
                    }

                    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
                    echo $nav;

                    if (($adm || ($db->query("SELECT `user_add` FROM `library_cats` WHERE `id`=" . $id)->fetchColumn() > 0)) && isset($id) && $systemUser->isValid()) {
                        echo '<p><a href="?act=addnew&amp;id=' . $id . '">' . _t('Write Article') . '</a>'
                            . ($adm ? ('<br><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
                                . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a>') : '')
                            . '</p>';
                    }
                }

                break;

            default:
                $row = $db->query("SELECT * FROM `library_texts` WHERE `id`=" . $id)->fetch();

                if ($row['premod'] || $adm) {

                    // Счетчик прочтений
                    if (!isset($_SESSION['lib']) || isset($_SESSION['lib']) && $_SESSION['lib'] != $id) {
                        $_SESSION['lib'] = $id;
                        $db->exec('UPDATE `library_texts` SET  `count_views`=' . ($row['count_views'] ? ++$row['count_views'] : 1) . ' WHERE `id`=' . $id);
                    }

                    // Запрашиваем выбранную статью из базы
                    $symbols = 7000;
                    $count_pages = ceil($db->query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id`= '" . $id . "' LIMIT 1")->fetchColumn() / $symbols);
                    if ($count_pages) {

                        // Чтоб всегда последнюю страницу считал правильно
                        $page = $page >= $count_pages ? $count_pages : $page;
                        $text = $db->query("SELECT SUBSTRING(`text`, " . ($page == 1 ? 1 : ($page - 1) * $symbols) . ", " . ($symbols + 100) . ") FROM `library_texts` WHERE `id`='" . $id . "'")->fetchColumn();
                        $tmp = mb_substr($text, $symbols, 100);
                    } else {
                        Utils::redir404();
                    }

                    $nav = $count_pages > 1 ? '<div class="topmenu">' . $tools->displayPagination('index.php?id=' . $id . '&amp;',
                            $page == 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>' : '';
                    $catalog = $db->query("SELECT `id`, `name` FROM `library_cats` WHERE `id` = " . $row['cat_id'] . " LIMIT 1")->fetch();
                    echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a>'
                        . ' | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $tools->checkout($catalog['name']) . '</a>'
                        . ($page > 1 ? ' | ' . $tools->checkout($row['name']) : '') . '</div>';

                    // Верхняя постраничная навигация
                    if ($count_pages > 1) {
                        echo '<div class="topmenu">' . $tools->displayPagination('index.php?id=' . $id . '&amp;',
                                $page == 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>';
                    }

                    if ($page == 1) {
                        echo '<div class="list2">';
                        // Заголовок статьи
                        echo '<h2>' . $tools->checkout($row['name']) . '</h2>';

                        // Описание к статье
                        $obj = new Hashtags($row['id']);
                        $rate = new Rating($row['id']);
                        $uploader = $row['uploader_id']
                            ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . $tools->checkout($row['uploader']) . '</a>'
                            : $tools->checkout($row['uploader']);
                        echo '<table class="desc">'
                            // Тэги
                            . ($obj->getAllStatTags()
                                ? '<tr><td class="caption">' . _t('The Tags') . ':</td>'
                                . '<td>' . $obj->getAllStatTags(1) . '</td></tr>'
                                : '')
                            // Кто добавил?
                            . '<tr>'
                            . '<td class="caption">' . _t('Who added') . ':</td>'
                            . '<td>' . $uploader . ' (' . $tools->displayDate($row['time']) . ')</td>'
                            . '</tr>'
                            // Рейтинг
                            . '<tr>'
                            . '<td class="caption">' . _t('Rating') . ':</td>'
                            . '<td>' . $rate->viewRate(1) . '</td>'
                            . '</tr>'
                            // Прочтений
                            . '<tr>'
                            . '<td class="caption">' . _t('Number of readings') . ':</td>'
                            . '<td>' . $row['count_views'] . '</td>'
                            . '</tr>'
                            // Комментарии
                            . '<tr>';
                        if ($row['comments']) {
                            echo '<td class="caption"><a href="?act=comments&amp;id=' . $row['id'] . '">' . _t('Comments') . '</a>:</td>'
                                . '<td>' . $row['comm_count'] . '</td>';
                        } else {
                            echo '<td class="caption">' . _t('Comments') . ':</td>'
                                . '<td>' . _t('Comments are closed') . '</td>';
                        }
                        echo '</tr></table>';

                        // Метки авторов
                        echo '</div>';
                    }

                    $text = $tools->checkout(mb_substr($text,
                        ($page == 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' '))),
                        (($count_pages == 1 || $page == $count_pages) ? $symbols : $symbols + min(Utils::position($tmp,
                                PHP_EOL), Utils::position($tmp, ' ')) - ($page == 1 ? 0 : min(Utils::position($text,
                                PHP_EOL), Utils::position($text, ' '))))), 1, 1);

                    $text = $tools->smilies($text, $systemUser->rights ? 1 : 0);

                    echo '<div class="list2" style="padding: 8px">';

                    if ($page == 1) {
                        // Картинка статьи
                        if (file_exists('../files/library/images/big/' . $id . '.png')) {
                            $img_style = 'width: 50%; max-width: 240px; height: auto; float: left; clear: both; margin: 10px';
                            echo '<a href="../files/library/images/orig/' . $id . '.png">'
                                . '<img style="' . $img_style . '" src="../files/library/images/big/' . $id . '.png" alt="screen" /></a>';
                        }
                    }

                    // Выводим текст статьи
                    echo $text .
                        '<div style="clear: both"></div>' .
                        '</div>';

                    echo '<div class="phdr">' . _t('Download file') . ' <a href="?act=download&amp;type=txt&amp;id=' . $id . '">txt</a>'
                        . ' | <a href="?act=download&amp;type=fb2&amp;id=' . $id . '">fb2</a></div>';

                    echo $nav . ($systemUser->isValid() && $page == 1 ? $rate->printVote() : '');

                    if ($adm || $db->query("SELECT `uploader_id` FROM `library_texts` WHERE `id` = " . $id)->fetchColumn() == $systemUser->id && $systemUser->isValid()) {
                        echo '<p><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
                            . '<a href="?act=del&amp;type=article&amp;id=' . $id . '">' . _t('Delete') . '</a></p>';
                    }
                } else {
                    Utils::redir404();
                }
        } // end switch
    } // end else !id
} // end else $act

require_once('../system/end.php');
