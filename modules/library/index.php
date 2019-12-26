<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Utility\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Library\Tree;
use Library\Hashtags;
use Library\Rating;
use Library\Utils;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var Assets $assets
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 */

$assets = di(Assets::class);
$config = di('config')['johncms'];
$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);

// Регистрируем языки модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Регистрируем автозагрузчик для классов библиотеки
$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Library', __DIR__ . '/classes');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

$adm = ($user->rights > 4) ? true : false;
$i = 0;

$textl = _t('Library');

$error = '';

// Ограничиваем доступ к Библиотеке
if (! $config['mod_lib'] && $user->rights < 7) {
    $error = _t('Library is closed');
} elseif ($config['mod_lib'] == 1 && ! $user->isValid()) {
    $error = _t('Access forbidden');
}

if ($error) {
    echo $view->render(
        'system::app/old_content',
        [
            'title'   => $textl,
            'content' => $tools->displayError($error),
        ]
    );
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
    $hdrsql = $db->query('SELECT `name` FROM `' . $tab . '` WHERE `id`=' . $id . ' LIMIT 1');

    $hdrres = '';
    if ($hdrsql->rowCount()) {
        $hdrres = $hdrsql->fetchColumn();
    }

    $hdr = htmlentities($hdrres, ENT_QUOTES, 'UTF-8');
    if ($hdr) {
        $textl .= ' | ' . (mb_strlen($hdr) > 30 ? $hdr . '...' : $hdr);
    }
}
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

if (! $config['mod_lib']) {
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
    require_once 'includes/' . $act . '.php';
} else {
    if (! $id) {
        echo '<div class="phdr"><strong>' . _t('Library') . '</strong></div>';
        echo '<div class="topmenu"><a href="?act=search">' . _t('Search') . '</a> | <a href="?act=tagcloud">' . _t('Tag Cloud') . '</a></div>';
        echo '<div class="gmenu"><p>';

        if ($adm) {
            // Считаем число статей, ожидающих модерацию
            $res = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod`=0')->fetchColumn();

            if ($res > 0) {
                echo '<div>' . _t('On moderation') . ': <a href="?act=premod">' . $res . '</a></div>';
            }
        }

        $res = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1")->fetchColumn();

        if ($res) {
            echo '<img src="' . $assets->url('images/old/add.gif') . '" alt="" class="icon"><a href="?act=new">' . _t('New Articles') . '</a> (' . $res . ')<br>';
        }

        echo '<img src="' . $assets->url('images/old/rate.gif') . '" alt="" class="icon"><a href="?act=top">' . _t('Rating articles') . '</a><br>' .
            '<img src="' . $assets->url('images/old/talk.gif') . '" alt="" class="icon"><a href="?act=lastcom">' . _t('Latest comments') . '</a>' .
            '</p></div>';

        $total = $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=0')->fetchColumn();
        $y = 0;

        if ($total) {
            $req = $db->query('SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE `parent`=0 ORDER BY `pos` ASC');

            while ($row = $req->fetch()) {
                $y++;
                echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                    . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> ('
                    . $db->query('SELECT COUNT(*) FROM `' . ($row['dir'] ? 'library_cats' : 'library_texts') . '` WHERE ' . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id']))->fetchColumn() . ')';

                if (! empty($row['description'])) {
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
                $actdir = $db->query(
                    'SELECT `id`, `dir` FROM `library_cats` WHERE '
                    . ($id !== null ? '`id`=' . $id : 1) . ' LIMIT 1'
                )->fetch();
                if ($actdir['id'] > 0) {
                    $actdir = $actdir['dir'];
                } else {
                    Utils::redir404();
                }
                echo '<div class="phdr">' . $dir_nav->printNavPanel() . '</div>';

                if ($actdir) {
                    $total = $db->query(
                        'SELECT COUNT(*) FROM `library_cats` WHERE '
                        . ($id !== null ? '`parent`=' . $id : '`parent`=0')
                    )->fetchColumn();
                    $nav = ($total > $user->config->kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' : '';
                    $y = 0;

                    if ($total) {
                        $sql = $db->query(
                            'SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE '
                            . ($id !== null ? '`parent`=' . $id : '`parent`=0') . ' ORDER BY `pos` ASC LIMIT ' . $start . ',' . $user->config->kmess
                        );
                        echo $nav;

                        while ($row = $sql->fetch()) {
                            $y++;
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> ('
                                . $db->query(
                                    'SELECT COUNT(*) FROM `' . ($row['dir'] ? 'library_cats' : 'library_texts') . '` WHERE '
                                    . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id'])
                                )->fetchColumn() . ' '
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
                    $page = $page >= ceil($total / $user->config->kmess) ? ceil($total / $user->config->kmess) : $page;
                    $start = $page == 1 ? 0 : ($page - 1) * $user->config->kmess;
                    $nav = ($total > $user->config->kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' : '';

                    if ($total) {
                        $sql2 = $db->query(
                            'SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, `announce`
                            FROM `library_texts`
                            WHERE `premod`=1 AND `cat_id`=' . $id . '
                            ORDER BY `id` DESC LIMIT ' . $start . ',' . $user->config->kmess
                        );
                        echo $nav;

                        while ($row = $sql2->fetch()) {
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . (file_exists(UPLOAD_PATH . 'library/images/small/' . $row['id'] . '.png')
                                    ? '<div class="avatar"><img src="../upload/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                                    : '')
                                . '<div class="righttable"><h4><a href="?id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a></h4>'
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

                    if (($adm || ($db->query('SELECT `user_add` FROM `library_cats` WHERE `id`=' . $id)->fetchColumn() > 0)) && isset($id) && $user->isValid()) {
                        echo '<p><a href="?act=addnew&amp;id=' . $id . '">' . _t('Write Article') . '</a>'
                            . ($adm ? ('<br><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
                                . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a>') : '')
                            . '</p>';
                    }
                }
                break;

            default:
                $row = $db->query('SELECT * FROM `library_texts` WHERE `id`=' . $id)->fetch();

                if ($row['premod'] || $adm) {
                    // Счетчик прочтений
                    if (! isset($_SESSION['lib']) || isset($_SESSION['lib']) && $_SESSION['lib'] != $id) {
                        $_SESSION['lib'] = $id;
                        $db->exec('UPDATE `library_texts` SET  `count_views`=' . ($row['count_views'] ? ++$row['count_views'] : 1) . ' WHERE `id`=' . $id);
                    }

                    // Запрашиваем выбранную статью из базы
                    $symbols = 7000;
                    $count_pages = ceil($db->query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id`= '" . $id . "' LIMIT 1")->fetchColumn() / $symbols);
                    if ($count_pages) {
                        // Чтоб всегда последнюю страницу считал правильно
                        $page = $page >= $count_pages ? $count_pages : $page;
                        $text = $db->query('SELECT SUBSTRING(`text`, ' . ($page == 1 ? 1 : ($page - 1) * $symbols) . ', ' . ($symbols + 100) . ") FROM `library_texts` WHERE `id`='" . $id . "'")->fetchColumn();
                        $tmp = mb_substr($text, $symbols, 100);
                    } else {
                        Utils::redir404();
                    }

                    $nav = $count_pages > 1 ? '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;', $page == 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>' : '';
                    $catalog = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `id` = ' . $row['cat_id'] . ' LIMIT 1')->fetch();
                    echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a>'
                        . ' | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $tools->checkout($catalog['name']) . '</a>'
                        . ($page > 1 ? ' | ' . $tools->checkout($row['name']) : '') . '</div>';

                    // Верхняя постраничная навигация
                    if ($count_pages > 1) {
                        echo '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;', $page == 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>';
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

                    $text = $tools->checkout(mb_substr($text, ($page == 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' '))), (($count_pages == 1 || $page == $count_pages) ? $symbols : $symbols + min(Utils::position($tmp, PHP_EOL), Utils::position($tmp, ' ')) - ($page == 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' '))))), 1, 1); // phpcs:ignore
                    $text = $tools->smilies($text, $user->rights ? 1 : 0);

                    echo '<div class="list2" style="padding: 8px">';

                    if ($page == 1) {
                        // Картинка статьи
                        if (file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png')) {
                            $img_style = 'width: 50%; max-width: 240px; height: auto; float: left; clear: both; margin: 10px';
                            echo '<a href="../upload/library/images/orig/' . $id . '.png">'
                                . '<img style="' . $img_style . '" src="../upload/library/images/big/' . $id . '.png" alt="screen" /></a>';
                        }
                    }

                    // Выводим текст статьи
                    echo $text .
                        '<div style="clear: both"></div>' .
                        '</div>';

                    echo '<div class="phdr">' . _t('Download file') . ' <a href="?act=download&amp;type=txt&amp;id=' . $id . '">txt</a>'
                        . ' | <a href="?act=download&amp;type=fb2&amp;id=' . $id . '">fb2</a></div>';

                    echo $nav . ($user->isValid() && $page == 1 ? $rate->printVote() : '');

                    if ($adm || $db->query('SELECT `uploader_id` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn() == $user->id && $user->isValid()) {
                        echo '<p><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
                            . '<a href="?act=del&amp;type=article&amp;id=' . $id . '">' . _t('Delete') . '</a></p>';
                    }
                } else {
                    Utils::redir404();
                }
        } // end switch
    } // end else !id
} // end else $act

echo $view->render(
    'system::app/old_content',
    [
        'title'   => $textl,
        'content' => ob_get_clean(),
    ]
);
