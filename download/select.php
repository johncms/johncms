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

require_once ("../incfiles/head.php");
if (!empty ($_GET['cat'])) {
    $cat = $_GET['cat'];
    provcat($cat);
    if ($rights == 4 || $rights >= 6) {
        echo
        "<form action='?act=upl' method='post' enctype='multipart/form-data'>
         Выберите файл(max $flsz кб.):<br/>
         <input type='file' name='fail'/><br/>
         Скриншот:<br/>
         <input type='file' name='screens'/><hr/>
Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a>
<br/><input name='screens1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать рисунок</a><hr/>
Описание:<br/>
       <textarea name='opis'></textarea><br/>
         Сохранить как(без расширения):<br/>
         <input type='text' name='newname'/><br/>
<input type='hidden' name='cat' value='"
        . $cat . "'/>
         <input type='submit' value='Загрузить'/><br/>
         </form>";
    }
    else {
        echo "Нет доступа!<br/>";
    }
    echo "&#187;<a href='?cat=" . $cat . "'>Вернуться</a><br/>";
}
else {
    echo "Ошибка:не выбрана категория<br/>";
}

?>