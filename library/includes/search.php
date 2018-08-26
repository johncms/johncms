<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

/*
-----------------------------------------------------------------
Функция подсветки результатов запроса
-----------------------------------------------------------------
*/
function ReplaceKeywords($search, $text)
{
    $search = str_replace('*', '', $search);

    return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $text);
}

/*
-----------------------------------------------------------------
Принимаем данные, выводим форму поиска
-----------------------------------------------------------------
*/
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ? $search_post : $search_get;
$search_t = isset($_REQUEST['t']);
echo '<div class="phdr"><a href="?"><strong>' . $lng['library'] . '</strong></a> | ' . $lng['search'] . '</div>'
    . '<div class="gmenu"><form action="?act=search" method="post"><div>'
    . '<input type="text" value="' . ($search ? functions::checkout($search) : '') . '" name="search" />'
    . '<input type="submit" value="' . $lng['search'] . '" name="submit" /><br />'
    . '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;' . $lng_lib['search_name']
    . '</div></form></div>';

/*
-----------------------------------------------------------------
Проверям на ошибки
-----------------------------------------------------------------
*/
$error = false;
if ($search && (mb_strlen($search) < 2 || mb_strlen($search) > 64))
    $error = $lng['error_search_length'];

if ($search && !$error) {
    /*
    -----------------------------------------------------------------
    Выводим результаты запроса
    -----------------------------------------------------------------
    */
    $array = explode(' ', $search);
    $count = count($array);
    $query = $db->quote($search);
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM `library_texts`
        WHERE MATCH (`" . ($search_t ? 'name' : 'text') . "`) AGAINST (? IN BOOLEAN MODE)"), 0);
    $stmt->execute([
        $query
    ]);
    $total = $stmt->fetchColumn();
        
    echo '<div class="phdr"><a href="?"><strong>' . $lng['library'] . '</strong></a> | ' . $lng['search_results'] . '</div>';
    
    if ($total > $kmess)
        echo '<div class="topmenu">' . functions::display_pagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>';
    if ($total) {
        $stmt = $db->prepare("
            SELECT *, MATCH (`" . ($search_t ? 'name' : 'text') . "`) AGAINST (? IN BOOLEAN MODE) AS `rel`
            FROM `library_texts`
            WHERE MATCH (`" . ($search_t ? 'name' : 'text') . "`) AGAINST (? IN BOOLEAN MODE)
            ORDER BY `rel` DESC
            LIMIT " . $start . ", " . $kmess
        );
        $stmt->execute([
            $query,
            $query
        ]);
        $i = 0;
        while ($res = $stmt->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            foreach ($array as $srch) {
                if (($pos = mb_strpos(strtolower($res['text']), strtolower(str_replace('*', '', $srch)))) !== false) {
                    break;
                }
            }
            if (!isset($pos) || $pos < 100) {
                $pos = 100;
            }
            $name = functions::checkout($res['name']);
            $text = functions::checkout(mb_substr($res['text'], ($pos - 100), 400), 1);
            if ($search_t) {
                foreach ($array as $val) {
                    $name = ReplaceKeywords($val, $name);
                }
            } else {
                foreach ($array as $val) {
                    $text = ReplaceKeywords($val, $text);
                }
            }
            echo '<strong><a href="index.php?id=' . $res['id'] . '">' . $name . '</a></strong><br />' . $text
                . ' <div class="sub"><span class="gray">' . $lng_lib['added'] . ':</span> ' . functions::checkout($res['author'])
                . ' <span class="gray">(' . functions::display_date($res['time']) . ')</span><br />'
                . '<span class="gray">' . $lng_lib['reads'] . ':</span> ' . $res['count_views']
                . '</div></div>';
            ++$i;
        }
    } else {
        echo '<div class="rmenu"><p>' . $lng['search_results_empty'] . '</p></div>';
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . intval($total) . '</div>';
    if ($total > $kmess) {
        echo '<div class="topmenu">' . functions::display_pagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>'
            . '<div><form action="?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '" method="post">'
            . '<input type="text" name="page" size="2"/>'
            . '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>'
            . '</form></div>';
    }
} else {
    if ($error) {
        echo functions::display_error($error);
    }
    echo '<div class="phdr"><small>' . $lng['search_help'] . '</small></div>';
}
echo '<p>' . ($search ? '<a href="?act=search">' . $lng['search_new'] . '</a><br />' : '')
    . '<a href="?">' . $lng['library'] . '</a></p>';