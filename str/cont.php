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

define('_IN_JOHNCMS', 1);

$headmod = 'contacts';
$textl = 'Контакты';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
if (!empty ($_SESSION['uid'])) {
    if (!empty ($_GET['act'])) {
        $act = $_GET['act'];
    }
    switch ($act) {
        case "trans" :
            include ("../pages/trans.$ras_pages");
            echo '<p><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;

        case "add" :
            echo "<form action='cont.php?act=edit&amp;add=1' method='post'>Введите ник<br/>";
            echo "<input type='text' name='nik' value='' /><br/>
 <input type='submit' value='Добавить' />
  </form>";
            echo "<p><a href='?'>В список</a><br/>";
            break;

        case "edit" :
            if (!empty ($_POST['nik'])) {
                $nik = check($_POST['nik']);
            }
            elseif (!empty ($_GET['nik'])) {
                $nik = check($_GET['nik']);
            }
            else {
                if (empty ($_GET['id'])) {
                    echo "Ошибка!<br/><a href='cont.php'>В контакты</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }

                $id = intval($_GET['id']);
                $nk = mysql_query("select * from `users` where id='" . $id . "';");
                $nk1 = mysql_fetch_array($nk);
                $nik = $nk1['name'];
            }
            if (!empty ($_GET['add'])) {
                $add = intval($_GET['add']);
            }
            $adc = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $nik . "';");
            $adc1 = mysql_num_rows($adc);
            $addc = mysql_query("select * from `users` where name='" . $nik . "';");
            $addc1 = mysql_num_rows($addc);
            if ($add == 1) {
                if ($adc1 == 0) {
                    if ($addc1 == 1) {
                        mysql_query("insert into `privat` values(0,'" . $foruser . "','','" . $realtime . "','','','','','0','" . $login . "','" . $nik . "','','');");
                        echo "Контакт добавлен<br/>";
                    }
                    else {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                }
                else {
                    echo "Данный логин уже есть в Ваших контактах<br/>";
                }
            }
            else {
                if ($adc1 == 1) {
                    if ($addc1 == 1) {
                        mysql_query("delete from `privat` where me='" . $login . "' and cont='" . $nik . "';");
                        echo "Контакт удалён<br/>";
                    }
                    else {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                }
                else {
                    echo "Этого логина нет в Ваших контактах<br/>";
                }
            }
            echo "<p><a href='?'>В контакты</a><br />";
            break;

        case "write" :
            if (!empty ($_GET['user'])) {
                $messages = mysql_query("select * from `users` where id='" . intval($_GET['user']) . "';");
                $userr = mysql_fetch_array($messages);
                $adresat = $userr['name'];
                $contime = mysql_query("select * from `privat` where me='$login' and cont='" . $userr['name'] . "';");
                $ctim = mysql_fetch_array($contime);
                $dtime = date("d.m.Y / H:i", $ctim['time']);
                echo "<form action='pradd.php?act=send' method='post' enctype='multipart/form-data'>
	 Для: $adresat<br/>";
                echo "Имя: $userr[imname]<br/>";
                if ($userr['sex'] == "m") {
                    echo "Парень<br/>";
                }
                if ($userr['sex'] == "zh") {
                    echo "Девушка<br/>";
                }
                echo "Дата добавления: $dtime<br/>";
                if ($userr['mailact'] == 1) {
                    if (!empty ($userr['icq'])) {
                        echo '<img src="http://web.icq.com/whitepages/online?icq=' . $userr['icq'] . '&amp;img=5" alt=""/> ICQ:' . $userr['icq'] . ' <br/> ';
                    }
                    if (!empty ($userr['mail'])) {
                        echo "E-mail:";
                        if ($userr['mailvis'] == 1) {
                            echo "$userr[mail]<br/>";
                        }
                        else {
                            echo "скрыт<br/>";
                        }
                    }
                }
                if (!empty ($userr['www']) && $userr['www'] !== "http://" && stristr($userr['www'], "http://")) {
                    echo "Сайт: <a href='" . $userr['www'] . "'>" . $userr['www'] . "</a><br/>";
                }

                echo "<input type='hidden' name='foruser' value='" . $adresat . "' />";
                echo
                " <br />Тема:<br />
<input type='text' name='tem' value='' />

<br /> Cообщение:<br />
        <textarea rows='5' name='msg'></textarea><br/>Прикрепить файл:<br />
         <input type='file' name='fail'/><hr/>
Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>
        <input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>
      <input type='submit' value='Отправить' />
  </form>";
                echo "<p><a href='cont.php?act=trans'>Транслит</a><br /><a href='smile.php'>Смайлы</a><br /><a href='?'>В список</a><br />";
            }
            else {
                echo '<p>Ошибка-не указан адресат<br/>';
            }
            break;

        default :
            echo '<div class="phdr">Контакты</div>';
            $contacts = mysql_query("select * from `privat` where me='$login' and cont!='';");
            $colcon = mysql_num_rows($contacts);
            while ($mass = mysql_fetch_array($contacts)) {
                $uz = mysql_query("select * from `users` where name='$mass[cont]';");
                $mass1 = mysql_fetch_array($uz);
                echo '<div class="menu"><a href="?act=write&amp;user=' . $mass1['id'] . '">' . $mass['cont'] . '</a>';
                $ontime = $mass1['lastdate'];
                $ontime2 = $ontime + 300;
                if ($realtime > $ontime2) {
                    echo '<font color="#FF0000"> [Off]</font>';
                }
                else {
                    echo '<font color="#00AA00"> [ON]</font>';
                }
                echo ' <a href="cont.php?act=edit&amp;id=' . $mass1['id'] . '">[X]</a></div>';
            }

            echo '<p><a href="?act=add">Добавить контакт</a><br />';
            break;
    }
}

echo "<a href='../index.php?act=cab'>В кабинет</a></p>";
require_once ("../incfiles/end.php");

?>