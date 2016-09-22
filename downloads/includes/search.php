<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Поиск файлов
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
$search = $search_post ? $search_post : $search_get;

// Форма для поиска
echo '<div class="phdr"><a href="' . $url . '"><b>' . $lng['download_title'] . '</b></a> | ' . $lng['search'] . '</div>' .
    '<form action="' . $url . '?act=search" method="post"><div class="gmenu"><p>' .
    $lng['name_file'] . ':<br /><input type="text" name="search" value="' . htmlspecialchars($search) . '" /><br />' .
    '<input name="id" type="checkbox" value="1" ' . ($id ? 'checked="checked"' : '') . '/> ' . $lng['search_for_desc'] . '<br />' .
    '<input type="submit" value="Поиск" name="submit" /><br />' .
    '</p></div></form>';

// Проверяем на коректность ввода
$error = false;

if (!empty($search) && mb_strlen($search) < 2 || mb_strlen($search) > 64) {
    $error = $lng['search_error'];
}

// Выводим результаты поиска
if ($search && !$error) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Подготавливаем данные для запроса
    $search = preg_replace("/[^\w\x7F-\xFF\s]/", " ", $search);
    $search_db = strtr($search, ['_' => '\\_', '%' => '\\%', '*' => '%']);
    $search_db = '%' . $search_db . '%';
    $search_db = $db->quote($search_db);
    $sql = ($id ? '`about`' : '`rus_name`') . ' LIKE ' . $search_db;

    // Результаты поиска
    echo '<div class="phdr"><b>' . $lng['search_result'] . '</b></div>';
    $total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND $sql")->fetchColumn();

    if ($total > $kmess) {
        $check_search = htmlspecialchars(rawurlencode($search));
        echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=search&amp;search=' . $check_search . '&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
    }

    if ($total) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND $sql ORDER BY `rus_name` ASC " . $db->pagination());
        $i = 0;

        while ($res_down = $req_down->fetch()) {
            echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
        }
    } else {
        echo '<div class="rmenu"><p>' . $lng['search_list_empty'] . '</p></div>';
    }

    echo '<div class="phdr">' . $lng['total'] . ':  ' . $total . '</div>';

    // Навигация
    if ($total > $kmess) {
        echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=search&amp;search=' . $check_search . '&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>' .
            '<p><form action="' . $url . '" method="get">' .
            '<input type="hidden" value="' . $check_search . '" name="search" />' .
            '<input type="hidden" value="search" name="act" />' .
            '<input type="hidden" value="' . $id . '" name="id" />' .
            '<input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="' . $url . '?act=search">' . $lng['search_new'] . '</a></p>';
} else {
    // FAQ по поиску и вывод ошибки
    if ($error) {
        echo $error;
    }

    echo '<div class="phdr"><small>' . $lng['search_faq'] . '</small></div>';
}

echo '<p><a href="' . $url . '">' . $lng['download_title'] . '</a></p>';
