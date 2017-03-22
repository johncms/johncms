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

$act = isset($_GET['act']) ? trim($_GET['act']) : '';

$headmod = 'forumsearch';
require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$textl = _t('Forum search');
require('../system/head.php');
echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Search') . '</div>';

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Функция подсветки результатов запроса
function ReplaceKeywords($search, $text)
{
    $search = str_replace('*', '', $search);

    return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $text);
}

switch ($act) {
    case 'reset':
        // Очищаем историю личных поисковых запросов
        if ($systemUser->isValid()) {
            if (isset($_POST['submit'])) {
                $db->exec("DELETE FROM `cms_users_data` WHERE `user_id` = '" . $systemUser->id . "' AND `key` = 'forum_search' LIMIT 1");
                header('Location: search.php');
            } else {
                echo '<form action="search.php?act=reset" method="post">' .
                    '<div class="rmenu">' .
                    '<p>' . _t('Do you really want to clear the search history?') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Clear') . '" /></p>' .
                    '<p><a href="search.php">' . _t('Cancel') . '</a></p>' .
                    '</div>' .
                    '</form>';
            }
        }
        break;

    default:
        // Принимаем данные, выводим форму поиска
        $search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
        $search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
        $search = $search_post ? $search_post : $search_get;
        //$search = preg_replace("/[^\w\x7F-\xFF\s]/", " ", $search);
        $search_t = isset($_REQUEST['t']);
        $to_history = false;
        echo '<div class="gmenu"><form action="search.php" method="post"><p>' .
            '<input type="text" value="' . ($search ? $tools->checkout($search) : '') . '" name="search" />' .
            '<input type="submit" value="' . _t('Search') . '" name="submit" /><br />' .
            '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;' . _t('Search in the topic names') .
            '</p></form></div>';

        // Проверям на ошибки
        $error = $search && mb_strlen($search) < 4 || mb_strlen($search) > 64 ? true : false;

        if ($search && !$error) {
            // Выводим результаты запроса
            $array = explode(' ', $search);
            $count = count($array);
            $query = $db->quote($search);
            $total = $db->query("
                SELECT COUNT(*) FROM `forum`
                WHERE MATCH (`text`) AGAINST ($query IN BOOLEAN MODE)
                AND `type` = '" . ($search_t ? 't' : 'm') . "'" . ($systemUser->rights >= 7 ? "" : " AND `close` != '1'
            "))->fetchColumn();
            echo '<div class="phdr">' . _t('Search results') . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>';
            }

            if ($total) {
                $to_history = true;
                $req = $db->query("
                    SELECT *, MATCH (`text`) AGAINST ($query IN BOOLEAN MODE) as `rel`
                    FROM `forum`
                    WHERE MATCH (`text`) AGAINST ($query IN BOOLEAN MODE)
                    AND `type` = '" . ($search_t ? 't' : 'm') . "'
                    ORDER BY `rel` DESC
                    LIMIT $start, $kmess
                ");
                $i = 0;

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

                    if (!$search_t) {
                        // Поиск только в тексте
                        $res_t = $db->query("SELECT `id`,`text` FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                        echo '<b>' . $res_t['text'] . '</b><br />';
                    } else {
                        // Поиск в названиях тем
                        $res_p = $db->query("SELECT `text` FROM `forum` WHERE `refid` = '" . $res['id'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();

                        foreach ($array as $val) {
                            $res['text'] = ReplaceKeywords($val, $res['text']);
                        }

                        echo '<b>' . $res['text'] . '</b><br />';
                    }

                    echo '<a href="../profile/?user=' . $res['user_id'] . '">' . $res['from'] . '</a> ';
                    echo ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span><br>';
                    $text = $search_t ? $res_p['text'] : $res['text'];

                    foreach ($array as $srch) {
                        if (($pos = mb_strpos(strtolower($res['text']), strtolower(str_replace('*', '', $srch)))) !== false) {
                            break;
                        }
                    }

                    if (!isset($pos) || $pos < 100) {
                        $pos = 100;
                    }

                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $text = $tools->checkout(mb_substr($text, ($pos - 100), 400), 1);

                    if (!$search_t) {
                        foreach ($array as $val) {
                            $text = ReplaceKeywords($val, $text);
                        }
                    }

                    echo $text;

                    if (mb_strlen($res['text']) > 500) {
                        echo '...<a href="index.php?act=post&amp;id=' . $res['id'] . '">' . _t('Read more') . ' &gt;&gt;</a>';
                    }

                    echo '<br /><a href="index.php?id=' . ($search_t ? $res['id'] : $res_t['id']) . '">' . _t('Go to Topic') . '</a>' . ($search_t ? ''
                            : ' | <a href="index.php?act=post&amp;id=' . $res['id'] . '">' . _t('Go to Message') . '</a>');
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="rmenu"><p>' . _t('Your search did not match any results') . '</p></div>';
            }
            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
        } else {
            if ($error) {
                echo $tools->displayError(_t('Invalid length'));
            }

            echo '<div class="phdr"><small>' . _t('Length of query: 4min., 64maks.<br>Search is case insensitive <br>Results are sorted by relevance.') . '</small></div>';
        }

        // Обрабатываем и показываем историю личных поисковых запросов
        if ($systemUser->isValid()) {
            $req = $db->query("SELECT * FROM `cms_users_data` WHERE `user_id` = '" . $systemUser->id . "' AND `key` = 'forum_search' LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $history = unserialize($res['val']);

                // Добавляем запрос в историю
                if ($to_history && !in_array($search, $history)) {
                    if (count($history) > 20) {
                        array_shift($history);
                    }

                    $history[] = $search;
                    $db->exec("UPDATE `cms_users_data` SET
                        `val` = " . $db->quote(serialize($history)) . "
                        WHERE `user_id` = '" . $systemUser->id . "' AND `key` = 'forum_search'
                        LIMIT 1
                    ");
                }

                sort($history);

                foreach ($history as $val) {
                    $history_list[] = '<a href="search.php?search=' . urlencode($val) . '">' . htmlspecialchars($val) . '</a>';
                }

                // Показываем историю запросов
                echo '<div class="topmenu">' .
                    '<b>' . _t('Search History') . '</b> <span class="red"><a href="search.php?act=reset">[x]</a></span><br />' .
                    implode(' | ', $history_list) .
                    '</div>';
            } elseif ($to_history) {
                $history[] = $search;
                $db->exec("INSERT INTO `cms_users_data` SET
                    `user_id` = '" . $systemUser->id . "',
                    `key` = 'forum_search',
                    `val` = " . $db->quote(serialize($history)) . "
                ");
            }
        }

        // Постраничная навигация
        if (isset($total) && $total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>' .
                '<p><form action="search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                '</form></p>';
        }

        echo '<p>' . ($search ? '<a href="search.php">' . _t('New Search') . '</a><br />' : '') . '<a href="index.php">' . _t('Forum') . '</a></p>';
}

require('../system/end.php');
