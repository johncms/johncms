<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Функция подсветки результатов запроса
function ReplaceKeywords($search, $text)
{
    $search = str_replace('*', '', $search);

    return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $text);
}

// Принимаем данные, выводим форму поиска
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ? $search_post : $search_get;
$search_t = isset($_REQUEST['t']);
echo '<div class="phdr"><a href="?"><strong>' . __('Library') . '</strong></a> | ' . __('Search') . '</div>'
    . '<div class="gmenu"><form action="?act=search" method="post"><div>'
    . '<input type="text" value="' . ($search ? $tools->checkout($search) : '') . '" name="search" />'
    . '<input type="submit" value="' . __('Search') . '" name="submit" /><br>'
    . '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;' . __('Search in titles Articles')
    . '</div></form></div>';

// Проверям на ошибки
$error = false;

if ($search && (mb_strlen($search) < 4 || mb_strlen($search) > 64)) {
    $error = __('Length of query: 4 min 64 max<br>Search is case-insensitive letters<br>Results are sorted by relevance');
}

if ($search && ! $error) {
    /** @var PDO $db */
    $db = di(PDO::class);

    // Выводим результаты запроса
    $array = explode(' ', $search);
    $count = count($array);
    $query = $db->quote($search);
    $total = $db->query(
        'SELECT COUNT(*) FROM `library_texts`
        WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)'
    )->fetchColumn();

    echo '<div class="phdr"><a href="?"><strong>' . __('Library') . '</strong></a> | ' . __('Search results') . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query(
            'SELECT *, MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE) AS `rel`
            FROM `library_texts`
            WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)
            ORDER BY `rel` DESC
            LIMIT ' . $start . ', ' . $user->config->kmess
        );

        while ($res = $req->fetch()) {
            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">';

            foreach ($array as $srch) {
                if (($pos = mb_strpos(strtolower($res['text']), strtolower(str_replace('*', '', $srch)))) !== false) {
                    break;
                }
            }

            if (! isset($pos) || $pos < 100) {
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

            echo '<strong><a href="?id=' . $res['id'] . '">' . $name . '</a></strong><br>' . $text
                . ' <div class="sub"><span class="gray">' . __('Who added') . ':</span> ' . $tools->checkout($res['author'])
                . ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span><br>'
                . '<span class="gray">' . __('Number of readings') . ':</span> ' . $res['count_views']
                . '</div></div>';
            ++$i;
        }
    } else {
        echo '<div class="rmenu"><p>' . __('Your search did not match any results') . '</p></div>';
    }

    echo '<div class="phdr">' . __('Total') . ': ' . (int) $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $user->config->kmess) . '</div>'
            . '<div><form action="?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '" method="post">'
            . '<input type="text" name="page" size="2"/>'
            . '<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/>'
            . '</form></div>';
    }
} else {
    if ($error) {
        echo $tools->displayError($error);
    }

    echo '<div class="phdr"><small>' . __('Length of query: 4 min 64 max<br>Search is case-insensitive letters<br>Results are sorted by relevance') . '</small></div>';
}

echo '<p>' . ($search ? '<a href="?act=search">' . __('New Search') . '</a><br>' : '')
    . '<a href="?">' . __('To Library') . '</a></p>';
