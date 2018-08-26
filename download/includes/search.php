<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once('../incfiles/head.php');
echo '<div class="phdr"><a href="index.php"><b>' . $lng['downloads'] . '</b></a> | ' . $lng['search'] . '</div>';
$srh = isset($_GET['srh']) ? rawurldecode(trim($_GET['srh'])) : '';

$error = false;
if (!empty($srh) && (mb_strlen($srh) < 2 || mb_strlen($srh) > 20)) {
    $error = $lng['error_wrong_lenght'];
}

if ($srh && !$error) {
    $search_db = functions::rus_lat(mb_strtolower($srh));
    $search_db = strtr($search_db, array (
        '_' => '\\_',
        '%' => '\\%'
    ));
    $search_db = $db->quote('%' . $search_db . '%');

    $stmt = $db->prepare('SELECT COUNT(*) FROM `download` WHERE `type`="file" AND `name` LIKE ? OR `text` LIKE ?');
    $stmt->execute([
        $search_db,
        $search_db
    ]);
    $total = $stmt->fetchColumn();
    if ($total) {
        $stmt = $db->prepare('SELECT * FROM `download` WHERE `type`="file" AND `name` LIKE ? OR `text` LIKE ? LIMIT ' . $start ', ' . $kmess);
        $stmt->execute([
            $search_db,
            $search_db
        ]);

        echo '<p>' . $lng['search_results'] . '</p>';
        $i = 0;
        while ($array = $stmt->fetch()) {
            echo '<div class="list' . (++$i % 2 + 1) . '">';
            if (stristr($array['name'], $srh)) {
                $res[] = $lng_dl['found_by_name'] . ":<br/><a href='?act=view&amp;file=" . $array ['id'] . "'>$array[name]</a><br/>";
            }
            if (stristr($array['text'], $srh)) {
                $res[] = $lng_dl['found_by_description'] . ":<br/><a href='?act=view&amp;file=" . $array ['id'] . "'>$array[name]</a><br/>$array[text]<br/>";
            }
            echo '</div>';
        }
        echo "<div class='phdr'>" . $lng['total'] . ": $total</div>";
        if ($total > $kmess) {
            echo functions::display_pagination('index.php?act=search&amp;srh=' . urlencode($srh) . '&amp;', $start, $total, $kmess);
            echo "<form action='index.php'>To Page:<br/><input type='hidden' name='act' value='search'/><input type='hidden' name='srh' value='" . urlencode($srh) .
                "'/><input type='text' name='page' /><br/><input type='submit' value='Go!'/></form>";
        }
    } else {
        echo '<p>' . $lng['search_results_empty'] . '</p>';
    }
} else {
    if ($error) {
        echo functions::display_error($error);
    }
}
echo '<p><a href="?">' . $lng['downloads'] . '</a></p>';