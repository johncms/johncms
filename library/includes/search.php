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

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Функция подсветки результатов запроса
function ReplaceKeywords($search, $text)
{
    $search = str_replace('*', '', $search);

    return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu',
        '<span style="background-color: #FFFF33">$1</span>', $text);
}

// Принимаем данные, выводим форму поиска
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ? $search_post : $search_get;
$search_t = isset($_REQUEST['t']);
echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a> | ' . _t('Search') . '</div>'
    . '<div class="gmenu"><form action="?act=search" method="post"><div>'
    . '<input type="text" value="' . ($search ? $tools->checkout($search) : '') . '" name="search" />'
    . '<input type="submit" value="' . _t('Search') . '" name="submit" /><br>'
    . '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;' . _t('Search in titles Articles')
    . '</div></form></div>';

// Проверям на ошибки
$error = false;

if ($search && (mb_strlen($search) < 4 || mb_strlen($search) > 64)) {
    $error = _t('Length of query: 4 min 64 max<br>Search is case-insensitive letters<br>Results are sorted by relevance');
}

if ($search && !$error) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Выводим результаты запроса
    $array = explode(' ', $search);
    $count = count($array);
    $query = $db->quote($search);
    $total = $db->query('
        SELECT COUNT(*) FROM `library_texts`
        WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)')->fetchColumn();

    echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a> | ' . _t('Search results') . '</div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;',
                $start, $total, $kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query('
            SELECT *, MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE) AS `rel`
            FROM `library_texts`
            WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)
            ORDER BY `rel` DESC
            LIMIT ' . $start . ', ' . $kmess
        );

        while ($res = $req->fetch()) {
            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">';

            foreach ($array as $srch) {
                if (($pos = mb_strpos(strtolower($res['text']), strtolower(str_replace('*', '', $srch)))) !== false) {
                    break;
                }
            }

            if (!isset($pos) || $pos < 100) {
                $pos = 100;
            }

            $name = $tools->checkout($res['name']);
            $text = $tools->checkout(mb_substr($res['text'], ($pos - 100), 400), 1);

            if ($search_t) {
                foreach ($array as $val) {
                    $name = ReplaceKeywords($val, $name);
                }
            } else {
                foreach ($array as $val) {
                    $text = ReplaceKeywords($val, $text);
                }
            }

            echo '<strong><a href="index.php?id=' . $res['id'] . '">' . $name . '</a></strong><br>' . $text
                . ' <div class="sub"><span class="gray">' . _t('Who added') . ':</span> ' . $tools->checkout($res['author'])
                . ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span><br>'
                . '<span class="gray">' . _t('Number of readings') . ':</span> ' . $res['count_views']
                . '</div></div>';
            ++$i;
        }
    } else {
        echo '<div class="rmenu"><p>' . _t('Your search did not match any results') . '</p></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . intval($total) . '</div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;',
                $start, $total, $kmess) . '</div>'
            . '<div><form action="?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '" method="post">'
            . '<input type="text" name="page" size="2"/>'
            . '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>'
            . '</form></div>';
    }
} else {
    if ($error) {
        echo $tools->displayError($error);
    }

    echo '<div class="phdr"><small>' . _t('Length of query: 4 min 64 max<br>Search is case-insensitive letters<br>Results are sorted by relevance') . '</small></div>';
}

echo '<p>' . ($search ? '<a href="?act=search">' . _t('New Search') . '</a><br>' : '')
    . '<a href="?">' . _t('To Library') . '</a></p>';
