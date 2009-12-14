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

if (!$user_id || $ban['1'] || $ban['14']) {
    header("location: index.php");
    exit;
}
if (empty ($_GET['id'])) {
    echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}

// Проверка на спам
$old = ($rights > 0) ? 10 : 60;
if ($datauser['lastpost'] > ($realtime - $old)) {
    require_once ("../incfiles/head.php");
    echo '<p><b>Антифлуд!</b><br />Порог ' . $old . ' секунд<br/><br/><a href="index.php?id=' . $id . '">Назад</a></p>';
    require_once ("../incfiles/end.php");
    exit;
}

$type = mysql_query("select * from `gallery` where id='" . $id . "';");
$ms = mysql_fetch_array($type);
if ($ms['type'] != "al") {
    echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$rz = mysql_query("select * from `gallery` where type='rz' and id='" . $ms['refid'] . "';");
$rz1 = mysql_fetch_array($rz);
if ((!empty ($_SESSION['uid']) && $rz1['user'] == 1 && $ms['text'] == $login) || $rights >= 6) {
    $dopras = array("gif", "jpg", "png");
    $tff = implode(" ,", $dopras);
    $fotsize = $flsz / 5;
    echo "Добавление фотографии:<br/>Разрешённые типы: $tff<br/>Максимальный вес: $fotsize кб.<br/><form action='index.php?act=load&amp;id=" . $id .
    "' method='post' enctype='multipart/form-data'>Выберите фото:<br/><input type='file' name='fail'/><hr/>Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/><a href='op:fileselect'>Выбрать фото</a><hr/>Подпись:<br/><textarea name='text'></textarea><br/><input type='submit' value='Загрузить'/><br/></form><a href='index.php?id="
    . $id . "'>Назад</a><br/>";
}
else {
    header("location: index.php");
}

?>