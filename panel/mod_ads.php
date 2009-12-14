<?

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
// Рекламный модуль от FlySelf
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7) {
    echo display_error('Доступ закрыт');
    require_once ('../incfiles/end.php');
    exit;
}

$array = array(2 => 'font-weight: bold;', 3 => 'font-style:italic;', 4 => 'text-decoration:underline;', 5 => 'font-weight: bold;font-style:italic;', 6 => 'font-weight: bold;text-decoration:underline;', 7 =>
'font-style:italic;text-decoration:underline;', 9 => 'font-weight: bold;font-style:italic;text-decoration:underline;');
$from = isset ($_GET['from']) ? $_GET['from'] : '';
switch ($from) {
    case 'recovery' :
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `id` = '" . $id . "'"), 0);
        if ($total == 0) {
            echo '<p>Вы ничего не выбрали<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;

        }
        if (isset ($_GET['yes'])) {
            mysql_query("UPDATE `cms_ads` SET `to`='0' WHERE `id`='" . $id . "';");
            header('Location: ' . $_SESSION['prd'] . '');
        }
        else {
            echo '<p>Вы действительно хотите востановить сылку?</p>';
            echo '<p><a href="index.php?act=mod_ads&amp;from=recovery&amp;id=' . $id . '&amp;yes">да</a> | <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">нет</a></p>';

        }
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
        break;

    case 'to' :
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `id` = '" . $id . "'"), 0);
        if ($total == 0) {
            echo '<p>Вы ничего не выбрали<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;

        }
        if (isset ($_GET['yes'])) {
            mysql_query("UPDATE `cms_ads` SET `to`='1' WHERE `id`='" . $id . "';");
            header('Location: ' . $_SESSION['prd'] . '');
        }
        else {
            echo '<p>Вы действительно хотите скрыть сылку?</p>';
            echo '<p><a href="index.php?act=mod_ads&amp;from=to&amp;id=' . $id . '&amp;yes">да</a> | <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">нет</a></p>';

        }
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
        break;

    case 'del' :
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `id` = '" . $id . "'"), 0);
        if ($total == 0) {
            echo '<p>Вы ничего не выбрали для удаления<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;

        }
        if (isset ($_GET['yes'])) {
            mysql_query("DELETE FROM `cms_ads` WHERE `id`='" . $id . "';");
            header('Location: ' . $_SESSION['prd'] . '');
        }
        else {
            echo '<p>Вы действительно хотите удалить сылку?</p>';
            echo '<p><a href="index.php?act=mod_ads&amp;from=del&amp;id=' . $id . '&amp;yes">да</a> | <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">нет</a></p>';

        }
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
        break;

    case 'edit' :
        $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id' LIMIT 1");
        if (!mysql_num_rows($req)) {
            echo '<p>Вы ничего не выбрали для изменения<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;

        }
        if (isset ($_POST['submit'])) {
            if (empty ($_POST['link']) || empty ($_POST['name']))
                $error = '<div>Не заполнено обязательное поле.</div>';
            $old = ($rights > 0 || $dostsadm = 1) ? 15 : 30;
            $spam = $lastpost > ($realtime - $old) ? 1 : false;
            if ($spam)
                $error = $error . '<div><b>Антифлуд!</b><br /> Порог ' . $old . ' секунд.</div>';
            if (!empty ($_POST['color'])) {
                $color = mb_substr(trim($_POST['color']), 0, 6);
                if (preg_match("/[^\da-zA-Z_]+/", $color))
                    $error = $error . '<div>Недопустимые символы в цвете.</div>';
                if (strlen($color) < 6)
                    $error = $error . '<div>Не правильно заполнено поле "цвет".</div>';
            }
            $type = abs(intval($_POST['type']));
            if ($type > 3 || $type < 0)
                $type = 0;
            $mesto = mb_substr(abs(intval($_POST['mesto'])), 0, 2);
            if (!empty ($mesto)) {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `mesto` = '" . $mesto . "' AND `type` = '$type' AND `id` != '$id'"), 0);
                if ($total != 0)
                    $error = $error . '<div>Данное место занято.</div>';

            }
            if ($error) {
                echo '<div class="b"><b>Упс, ошибочка!</b></div>';
                echo '<div class="c">' . $error . '</div>';
                echo '<div class="b"><a href="index.php?act=mod_ads&amp;from=edit&amp;id=' . $id . '">Назад</a></div>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $link = mysql_real_escape_string(trim($_POST['link']));
            $name = mysql_real_escape_string(trim($_POST['name']));
            $font_1 = isset ($_POST['font_1']) ? 2 : 0;
            $font_2 = isset ($_POST['font_2']) ? 3 : 0;
            $font_3 = isset ($_POST['font_3']) ? 4 : 0;
            $font = $font_1 + $font_2 + $font_3;
            $view = abs(intval($_POST['view']));
            $day = abs(intval($_POST['day']));
            $count = abs(intval($_POST['count']));
            $day = abs(intval($_POST['day']));
            $layout = abs(intval($_POST['layout']));
            if ($mesto == 0) {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads`"), 0);
                $req = mysql_query("SELECT `mesto` FROM `cms_ads` ORDER BY `mesto` DESC LIMIT 1");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $mesto = $res['mesto'] + 1;
                }
                else {
                    $mesto = 1;
                }
            }
            mysql_query("UPDATE `cms_ads` SET
              `type`='" . $type . "',
              `view`='" . $view . "',
              `font`='" . $font . "',
              `mesto`='" . $mesto . "',
              `link`='" . $link . "',
              `name`='" . $name . "',
              `color`='" . $color . "',
              `count_link`='" . $count . "',
              `day`='" . $day . "',
              `layout`='" . $layout . "',
               `to`='0',
              `time`='" . $realtime . "' WHERE `id` = '" . $id . "';");
            mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
            echo 'Cылка успешно изменена<br /><a href="' . $_SESSION['prd'] . '">Продолжить</a>';
        }
        else {
            $arr = mysql_fetch_array($req);
            echo '<div class="phdr"><b>Редактирование ссылки</b></div>
            <form action="index.php?act=mod_ads&amp;from=edit&amp;id=' . $id . '" method="post">
            <div class="menu"><b>Ссылка:</b><br /><input type="text" name="link" value="' . htmlentities($arr['link'], ENT_QUOTES, 'UTF-8') . '"/><br />
<b>Название:</b><br />
            <input type="text" name="name" value="' . htmlentities($arr['name'], ENT_QUOTES, 'UTF-8') .
            '"/><br /><small>Для смены названия при обновление страницы перечислите все названия через знак "|".</small> <br />
<b>Цвет:</b><br />
            <input type="text" name="color" value="'
            . $arr['color'] .
            '"/><br /><small>В формате FFFFFF, если не хотите выделять ссылку цветом, то просто не заполняйте это поле.</small> <br />
<b>Переходы:</b><br />
            <input type="text" name="count"  value="'
            . $arr['count_link'] .
            '"/><br /><small>Колличество переходов, после котого ссылка автоматически уберется. 0 - без ограничей.</small> <br />
<b>Дни:</b><br />
            <input type="text" name="day"  value="'
            . $arr['day'] .
            '"/><br /><small>Колличество дней с момента установки ссылки, поистечению которого ссылка автоматически уберется. 0 - без ограничей.</small> <br />
 <b>Место:</b>
            <input type="text" name="mesto" size="2" value="'
            . $arr['mesto'] .
            '"/>
      <br /><small>Если оставить пустым, то сылка будет добавлена в конец списка.</small> <br />

</div><div class="gmenu"><b>Показывать:</b><br />
  <input type="radio" name="type" value="0" '
            . ($arr['type'] == 0 ? 'checked="checked"' : '') . '/> Над логотипом<br/>
    <input type="radio" name="type" value="1" ' . ($arr['type'] == 1 ? 'checked="checked"' : '') .
            '/> Под меню юзера<br/>
    <input type="radio" name="type" value="2" ' . ($arr['type'] == 2 ? 'checked="checked"' : '') . '/> Над счетчиками<br/>
    <input type="radio" name="type" value="3" ' . ($arr[
            'type'] == 3 ? 'checked="checked"' : '') . '/> Под счетчиками<br/>
<b>Размещение:</b><br />
  <input type="radio" name="layout" value="0" ' . ($arr['layout'] == 0 ? 'checked="checked"' : '') .
            '/> На всех страницах<br/>
    <input type="radio" name="layout" value="1"  ' . ($arr['layout'] == 1 ? 'checked="checked"' : '') .
            '/> Только на главной<br/>
     <input type="radio" name="layout" value="2"  ' . ($arr['layout'] == 2 ? 'checked="checked"' : '') .
            '/> На всех, кроме главной<br/>

<b>Выделение:</b><br />
<input type="checkbox" name="font_1" ' . ($arr['font'] == 2 || $arr['font'] == 5 || $arr['font'] == 6 || $arr['font'] == 9 ? 'checked="checked"'
            : '') . '/> <b>Жирный</b><br/>
    <input type="checkbox" name="font_2" ' . ($arr['font'] == 3 || $arr['font'] == 5 || $arr['font'] == 7 || $arr['font'] == 9 ? 'checked="checked"' : '') .
            '/>  <i>Наклонный</i><br/>
     <input type="checkbox" name="font_3"  ' . ($arr['font'] == 4 || $arr['font'] == 7 || $arr['font'] == 6 || $arr['font'] == 9 ? 'checked="checked"' : '') .
            '/>  <u>Подчеркнутый</u><br/>
 </div>
  <div class="phdr">

     <input type="submit" name="submit" value="Изменить" />
   </div>
  </form>';
            $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
            echo '<a href="' . $_SESSION['prd'] . '">Назад</a><br /><a href="index.php?act=mod_ads&amp;">В админку</a>';
        }
        break;

    case 'addlink' :
        if (isset ($_POST['submit'])) {
            if (empty ($_POST['link']) || empty ($_POST['name']))
                $error = '<div>Не заполнено обязательное поле.</div>';
            $old = ($rights > 0 || $dostsadm = 1) ? 15 : 30;
            $spam = $datauser['lastpost'] > ($realtime - $old) ? 1 : false;
            if ($spam)
                $error = $error . '<div><b>Антифлуд!</b><br /> Порог ' . $old . ' секунд.</div>';
            $type = abs(intval($_POST['type']));
            if ($type > 3 || $type < 0)
                $type = 0;
            $mesto = mb_substr(abs(intval($_POST['mesto'])), 0, 2);
            if (!empty ($mesto)) {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `mesto` = '" . $mesto . "' AND `type` = '" . $type . "'"), 0);
                if ($total != 0)
                    $error = $error . '<div>Данное место занято.</div>';

            }
            if (!empty ($_POST['color'])) {
                $color = mb_substr(trim($_POST['color']), 0, 6);
                if (preg_match("/[^\da-zA-Z_]+/", $color))
                    $error = $error . '<div>Недопустимые символы в цвете.</div>';
                if (strlen($color) < 6)
                    $error = $error . '<div>Не правильно заполнено поле "цвет".</div>';
            }
            if ($error) {
                echo '<div class="b"><b>Упс, ошибочка!</b></div>';
                echo '<div class="c">' . $error . '</div>';
                echo '<div class="p"><a href="index.php?act=mod_ads&amp;from=addlink">Назад</a></div>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $link = mysql_real_escape_string(trim($_POST['link']));
            $name = mysql_real_escape_string(trim($_POST['name']));
            $font_1 = isset ($_POST['font_1']) ? 2 : 0;
            $font_2 = isset ($_POST['font_2']) ? 3 : 0;
            $font_3 = isset ($_POST['font_3']) ? 4 : 0;
            $font = $font_1 + $font_2 + $font_3;
            $view = abs(intval($_POST['view']));
            $day = abs(intval($_POST['day']));
            $count = abs(intval($_POST['count']));
            $day = abs(intval($_POST['day']));
            $layout = abs(intval($_POST['layout']));
            if ($mesto == 0) {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads`"), 0);
                $req = mysql_query("SELECT `mesto` FROM `cms_ads` ORDER BY `mesto` DESC LIMIT 1");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $mesto = $res['mesto'] + 1;
                }
                else {
                    $mesto = 1;
                }
            }
            mysql_query("INSERT INTO `cms_ads` SET
              `type`='" . $type . "',
              `view`='" . $view . "',
              `font`='" . $font . "',
              `mesto`='" . $mesto . "',
              `link`='" . $link
            . "',
              `name`='" . $name . "',
              `color`='" . $color . "',
              `count_link`='" . $count . "',
              `day`='" . $day . "',
              `layout`='" . $layout .
            "',
               `to`='0',
              `time`='" . $realtime . "';");
            mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
            $type_array = array('up_1', 'up_2', 'down_2', 'down_1');
            echo 'Cылка успешно добавлена<br /><a href="index.php?act=mod_ads&amp;from=active&amp;' . $type_array[$type] . '">Продолжить</a>';
        }
        else {
            echo
            '<div class="phdr"><b>Добавление ссылки</b></div><form action="index.php?act=mod_ads&amp;from=addlink" method="post"><div class="menu">
<b>Ссылка:</b><br />
            <input type="text" name="link" value="http://"/><br />

<b>Название:</b><br />
            <input type="text" name="name"/><br /><small>Для смены названия при обновление страницы перечислите все названия через знак "|".</small> <br />
<b>Цвет:</b><br />
            <input type="text" name="color"/><br /><small>В формате FFFFFF, если не хотите выделять ссылку цветом, то просто не заполняйте это поле.</small> <br />

<b>Переходы:</b><br />
            <input type="text" name="count" value="0"/><br /><small>Колличество переходов, после котого ссылка автоматически уберется. 0 - без ограничей.</small> <br />

<b>Дни:</b><br />
            <input type="text" name="day" value="0"/><br /><small>Колличество дней с момента установки ссылки, поистечению которого ссылка автоматически уберется. 0 - без ограничей.</small> <br />
 <b>Место:</b>
            <input type="text" name="mesto" size="2"/>
      <br /><small>Если оставить пустым, то сылка будет добавлена в конец списка.</small> <br />

</div><div class="gmenu"><b>Показывать:</b><br />
  <input type="radio" name="view" value="0" checked="checked"/> Всем<br/>
    <input type="radio" name="view" value="1" /> Гостям<br/>
     <input type="radio" name="view" value="2" /> Пользователям<br/>
<b>Расположение:</b><br />
  <input type="radio" name="type" value="0" checked="checked"/> Над логотипом<br/>
    <input type="radio" name="type" value="1" /> Под меню юзера<br/>
    <input type="radio" name="type" value="2" /> Над счетчиками<br/>
    <input type="radio" name="type" value="3" /> Под счетчиками<br/>
   <b>Размещение:</b><br />
  <input type="radio" name="layout" value="0" checked="checked"/> На всех страницах<br/>
    <input type="radio" name="layout" value="1" /> Только на главной<br/>
     <input type="radio" name="layout" value="2" /> На всех, кроме главной<br/>

<b>Выделение:</b><br />
<input type="checkbox" name="font_1"/> <b>Жирный</b><br/>
    <input type="checkbox" name="font_2"/> <i>Наклонный</i><br/>
     <input type="checkbox" name="font_3"/> <u>Подчеркнутый</u><br/>
 </div>
  <div class="phdr">

     <input type="submit" name="submit" value="Добавить" />
   </div>
  </form>';

            echo '<p><a href="index.php?act=mod_ads&amp;from=active">Активные сылки</a><br /><a href="index.php?act=mod_ads&amp;">В админку</a></p>';
        }
        break;

    case 'down' :
        if ($id) {
            $req = mysql_query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '" . $id . "'");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                $mesto = $res['mesto'];
                $req = mysql_query("SELECT * FROM `cms_ads` WHERE `mesto` > '" . $mesto . "' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` ASC");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '" . $mesto2 . "' WHERE `id` = '" . $id . "'");
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '" . $mesto . "' WHERE `id` = '" . $id2 . "'");
                }
            }
        }
        header('Location: ' . getenv("HTTP_REFERER") . '');
        break;

    case 'up' :
        if ($id) {
            $req = mysql_query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '" . $id . "'");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                $mesto = $res['mesto'];
                $req = mysql_query("SELECT * FROM `cms_ads` WHERE `mesto` < '" . $mesto . "' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` DESC");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '" . $mesto2 . "' WHERE `id` = '" . $id . "'");
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '" . $mesto . "' WHERE `id` = '" . $id2 . "'");
                }
            }
        }
        header('Location: ' . getenv("HTTP_REFERER") . '');
        break;

    case 'view' :
        $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id' LIMIT 1");
        if (!mysql_num_rows($req)) {
            echo '<p>Вы ничего не выбрали для просмотра<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;

        }
        $arr = mysql_fetch_array($req);
        echo '<div class="phdr"><b>Просмотр сылки</b></div><div class="b">
            <b>Сылка:</b> <a href="' . htmlentities($arr['link'], ENT_QUOTES, 'UTF-8') . '">' . htmlentities($arr['link'], ENT_QUOTES, 'UTF-8') .
        '</a><br />
            <b>Название(ия):</b>  ' . htmlentities($arr['name'], ENT_QUOTES, 'UTF-8') . '<br />';
        if (!empty ($arr['color']))
            echo '<b>Цвет:</b> <span style="color:#' . $arr['color'] . '">' . $arr['color'] . '</span><br />';
        echo '<b>Переходов:</b>  ' . $arr['count'] . '';
        if (!empty ($arr['count_link']))
            echo ' из ' . $arr['count_link'];
        echo '<br />';
        if (!empty ($arr['day']))
            echo ' <b>Кол-во дней:</b> ' . $arr['day'] . '<br />';
        echo '<b>Дата установки:</b>  ' . date('d.m.y в H:i', $arr['time'] + $sdvig) . '<br />';
        echo '<b>Показывать:</b> ';
        if ($arr['view'] == 0)
            echo 'Всем<br/> ';
        elseif ($arr['view'] == 1)
            echo 'Гостям<br/> ';
        elseif ($arr['view'] == 2)
            echo 'Пользователям<br/> ';
        echo '<b>Расположение:</b> ';
        $array_type = array('Над логотипом', 'Под меню юзера', 'Над счетчиками', 'Под счетчиками');
        echo $array_type[$arr['type']] . '<br />';
        echo '<b>Размещение:</b> ';
        if ($arr['layout'] == 0)
            echo 'На всех страницах<br/>';
        elseif ($arr['layout'] == 1)
            echo 'Только на главной<br/>';
        elseif ($arr['layout'] == 2)
            echo 'На всех, кроме главной<br/> ';
        echo '<b>Место:</b>  ' . $arr['mesto'] . '<br />';
        echo '<b>Выделение:</b> ';
        if ($arr['font'] == 2 || $arr['font'] == 5 || $arr['font'] == 6 || $arr['font'] == 9)
            echo '<b>Ж</b> ';
        elseif ($arr['font'] == 3 || $arr['font'] == 5 || $arr['font'] == 7 || $arr['font'] == 9)
            echo '<i>I</i> ';
        elseif ($arr['font'] == 4 || $arr['font'] == 7 || $arr['font'] == 6 || $arr['font'] == 9)
            echo '<u>U</u>';
        echo '</div>';
        echo '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br /><a href="index.php?act=mod_ads&amp;">В админку</a>';
        break;

    case 'active' :
        if (isset ($_GET['down_1']))
            $_SESSION['abs_link'] = 3;
        elseif (isset ($_GET['down_2']))
            $_SESSION['abs_link'] = 2;
        elseif (isset ($_GET['up_2']))
            $_SESSION['abs_link'] = 1;
        elseif (isset ($_GET['up_1']))
            $_SESSION['abs_link'] = 0;
        echo '<div class="phdr"><b>Активные ссылки</b></div><div class="bmenu">';
        $sort = $_SESSION['abs_link'] ? intval($_SESSION['abs_link']) : 0;
        switch ($sort) {
            case '3' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=active&amp;up_1">Над логотипом</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;up_2">Под меню юзера</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;down_2">Над счетчиками</a> | Под счетчиками';
                break;
            case '2' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=active&amp;up_1">Над логотипом</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;up_2">Под меню юзера</a>
       | Над счетчиками | <a href="index.php?act=mod_ads&amp;from=active&amp;down_1">Под счетчиками</a>';
                break;
            case '1' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=active&amp;up_1">Над логотипом</a> | Под меню юзера
       | <a href="index.php?act=mod_ads&amp;from=active&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;down_1">Под счетчиками</a>';
                break;
            case '1' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=active&amp;up_1">Над логотипом</a> | Под меню юзера
       | <a href="index.php?act=mod_ads&amp;from=active&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;down_1">Под счетчиками</a>';
                break;
            default :
                echo
                'Над логотипом | <a href="index.php?act=mod_ads&amp;from=active&amp;up_2">Под меню юзера</a>
       | <a href="index.php?act=mod_ads&amp;from=active&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=active&amp;down_1">Под счетчиками</a>';

        }
        echo '</div><div class="gmenu"><a href="index.php?act=mod_ads&amp;from=addlink">Добавить ссылку</a></div>';
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `to` = '0' AND `type` = '$sort'"), 0);
        if ($total > 0) {
            $req = mysql_query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND `type` = '" . $_SESSION['abs_link'] . "' ORDER BY  `mesto` ASC LIMIT " . $start . "," . $kmess);
            while ($arr = mysql_fetch_array($req)) {
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                $name = explode("|", $arr['name']);
                $count = count($name);
                $name = htmlentities($name[0], ENT_QUOTES, 'UTF-8');
                if (!empty ($arr['color']))
                    $name = '<span style="color:#' . $arr['color'] . '">' . $name . '</span>';
                if (!empty ($arr['font']))
                    $name = '<span style="' . $array [$arr['font']] . '">' . $name . '</span>';
                if ($count > 1)
                    $name = $name . '...';
                echo '<a href="index.php?act=mod_ads&amp;from=view&amp;id=' . $arr['id'] . '">' . $name . '</a>';
                echo ' <br /><small><a href="index.php?act=mod_ads&amp;from=edit&amp;id=' . $arr['id'] . '">Изм.</a> | <a href="index.php?act=mod_ads&amp;from=del&amp;id=' . $arr['id'] . '">Удал.</a> | <a href="index.php?act=mod_ads&amp;from=to&amp;id=' . $arr['id'] .
                '">Скрыть</a>';
                echo ' | <a href="index.php?act=mod_ads&amp;from=up&amp;id=' . $arr['id'] . '">Вверх</a> | <a href="index.php?act=mod_ads&amp;from=down&amp;id=' . $arr['id'] . '">Вниз</a></small>';
                echo '</div>';
                ++$i;
            }
        }
        else {
            echo '<div class="c">Сылок нет.</div>';
        }

        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            //TODO: Исправить навигацию
            echo '<p>' . pagenav('index.php?from=active&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php" method="get"><input type="hidden" name="from" value="active"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php?act=mod_ads&amp;">Назад</a></p>';
        break;

    case 'noactive' :
        if (isset ($_GET['down_1']))
            $_SESSION['abs_link'] = 3;
        else
            if (isset ($_GET['down_2']))
                $_SESSION['abs_link'] = 2;
            else
                if (isset ($_GET['up_2']))
                    $_SESSION['abs_link'] = 1;
                else
                    if (isset ($_GET['up_1']))
                        $_SESSION['abs_link'] = 0;
                    echo '<div class="phdr"><b>Активные ссылки</b></div><div class="bmenu">';
        $sort = $_SESSION['abs_link'] ? intval($_SESSION['abs_link']) : 0;
        switch ($sort) {
            case '3' :
                echo '<a href="index.php?act=mod_ads&amp;from=noactive&amp;up_1">Над логотипом</a> | ';
                echo '<a href="index.php?act=mod_ads&amp;from=noactive&amp;up_2">Под меню юзера</a> | ';
                echo '<a href="index.php?act=mod_ads&amp;from=noactive&amp;down_2">Над счетчиками</a> | Под счетчиками';
                break;
            case '2' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=noactive&amp;up_1">Над логотипом</a> | <a href="index.php?act=mod_ads&amp;from=noactive&amp;up_2">Под меню юзера</a>
                | Над счетчиками | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_1">Под счетчиками</a>';
                break;
            case '1' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=noactive&amp;up_1">Над логотипом</a> | Под меню юзера
                | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_1">Под счетчиками</a>';
                break;
            case '1' :
                echo
                '<a href="index.php?act=mod_ads&amp;from=noactive&amp;up_1">Над логотипом</a> | Под меню юзера
       | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_1">Под счетчиками</a>';
                break;
            default :
                echo
                'Над логотипом | <a href="index.php?act=mod_ads&amp;from=noactive&amp;up_2">Под меню юзера</a>
       | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_2">Над счетчиками</a> | <a href="index.php?act=mod_ads&amp;from=noactive&amp;down_1">Под счетчиками</a>';

        }
        echo '</div><div class="gmenu"><a href="index.php?act=mod_ads&amp;from=addlink">Добавить ссылку</a></div>';
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `to` = '1' AND `type` = '$sort'"), 0);
        if ($total > 0) {
            $req = mysql_query("SELECT * FROM `cms_ads` WHERE `to` = '1' AND `type` = '" . $_SESSION['abs_link'] . "' ORDER BY  `mesto` ASC LIMIT " . $start . "," . $kmess);
            while ($arr = mysql_fetch_array($req)) {
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                $name = explode("|", $arr['name']);
                $count = count($name);
                $name = htmlentities($name[0], ENT_QUOTES, 'UTF-8');
                if (!empty ($arr['color']))
                    $name = '<span style="color:#' . $arr['color'] . '">' . $name . '</span>';
                if (!empty ($arr['font']))
                    $name = '<span style="' . $array [$arr['font']] . '">' . $name . '</span>';
                if ($count > 1)
                    $name = $name . '...';
                echo '<a href="index.php?act=mod_ads&amp;from=view&amp;id=' . $arr['id'] . '">' . $name . '</a>';
                echo ' <br /><small><a href="index.php?act=mod_ads&amp;from=edit&amp;id=' . $arr['id'] . '">Изм.</a> | <a href="index.php?act=mod_ads&amp;from=del&amp;id=' . $arr['id'] . '">Удал.</a> | <a href="index.php?act=mod_ads&amp;from=recovery&amp;id=' . $arr['id'] .
                '">Вост.</a>';
                echo ' | <a href="index.php?act=mod_ads&amp;from=up&amp;id=' . $arr['id'] . '">Вверх</a> | <a href="index.php?act=mod_ads&amp;from=down&amp;id=' . $arr['id'] . '">Вниз</a></small>';
                echo '</div>';
                ++$i;
            }
        }
        else {
            echo '<div class="c">Сылок нет.</div>';
        }

        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            //TODO: Исправить навигацию
            echo '<p>' . pagenav('index.php?from=noactive&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php" method="get"><input type="hidden" name="from" value="noactive"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php?act=mod_ads&amp;">Назад</a></p>';
        break;

    case 'clean' :
        if (isset ($_POST['submit'])) {
            $cl = isset ($_POST['cl']) ? intval($_POST['cl']) : '';
            switch ($cl) {
                case '1' :
                    mysql_query("DELETE FROM `cms_ads` WHERE `to`='0';");
                    mysql_query("OPTIMIZE TABLE `cms_ads`;");
                    header('location: index.php?act=mod_ads');
                    break;

                case '2' :
                    mysql_query("DELETE FROM `cms_ads`;");
                    mysql_query("OPTIMIZE TABLE `cms_ads`;");
                    header('location: index.php?act=mod_ads');
                    break;

                default :
                    mysql_query("DELETE FROM `cms_ads` WHERE `to`='1';");
                    mysql_query("OPTIMIZE TABLE `cms_ads`;");
                    header('location: index.php?act=mod_ads');
            }
        }
        else {
            echo '<p><b>Чистим список</b></p>';
            echo '<u>Что чистим?</u>';
            echo '<form id="clean" method="post" action="index.php?act=mod_ads&amp;from=clean">';
            echo '<input type="radio" name="cl" value="0" checked="checked" />Скрытые<br />';
            echo '<input type="radio" name="cl" value="1" />Активные<br />';
            echo '<input type="radio" name="cl" value="2" />Очищаем все<br />';
            echo '<input type="submit" name="submit" value="Очистить" />';
            echo '</form>';
            echo '<p><a href="index.php?act=mod_ads">Отмена</a></p>';
        }
        break;

    default :
        echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Реклама</div>';
        echo '<div class="gmenu"><a href="index.php?act=mod_ads&amp;from=addlink">Добавить ссылку</a></div>';
        $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `to` = '0'"), 0);
        echo '<div class="menu"><a href="index.php?act=mod_ads&amp;from=active">Активные ссылки</a> (' . $count . ')</div>';
        $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `to` = '1'"), 0);
        echo '<div class="menu"><a href="index.php?act=mod_ads&amp;from=noactive">Скрытые ссылки</a> (' . $count . ')</div>';
        echo '<div class="phdr"><a href="index.php?act=mod_ads&amp;from=clean">Очистить список</a></div>';
        echo '<p><a href="index.php">Админ-панель</a></p>';
}

?>
