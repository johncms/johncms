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
session_name('SESID');
session_start();
$headmod = 'ignor';
$textl = 'Игнор-лист';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
if (!empty ($_SESSION['uid'])) {
    if (!empty ($_GET['act'])) {
        $act = $_GET['act'];
    }
    switch ($act) {
        case "add" :
            echo '<div class="phdr">Добавить в игнор</div>';
            echo "<form action='ignor.php?act=edit&amp;add=1' method='post'>
	 Введите ник<br/>";
            echo "<input type='text' name='nik' value='' /><br/>
 <input type='submit' value='Добавить' />
  </form>";
            echo '<p><a href="ignor.php">В список</a><br/>';
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
                    echo "Ошибка!<br/><a href='ignor.php'>В список</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $nk = mysql_query("select * from `users` where id='" . $id . "';");
                $nk1 = mysql_fetch_array($nk);
                $nik = $nk1['name'];
            }
            if (!empty ($_GET['add'])) {
                $add = intval($_GET['add']);
            }
            $adc = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $nik . "';");
            $adc1 = mysql_num_rows($adc);
            $addc = mysql_query("select * from `users` where name='" . $nik . "';");
            $addc2 = mysql_fetch_array($addc);
            $addc1 = mysql_num_rows($addc);
            if ($add == 1) {
                if ($addc2['rights'] >= 1 || $nik == $nickadmina || $nik == $nickadmina) {
                    echo '<p>Администрацию нельзя в игнор!!!<br/><a href="ignor.php">В список</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if ($adc1 == 0) {
                    if ($addc1 == 1) {
                        mysql_query("insert into `privat` values(0,'" . $foruser . "','','" . $realtime . "','','','','','0','" . $login . "','','" . $nik . "','');");
                        echo "Юзер добавлен в игнор<br/>";
                    }
                    else {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                }
                else {
                    echo "Данный логин уже есть в Вашем игноре<br/>";
                }
            }
            else {
                if ($adc1 == 1) {
                    if ($addc1 == 1) {
                        mysql_query("delete from `privat` where me='" . $login . "' and ignor='" . $nik . "';");
                        echo "Юзер удалён из игнора<br/>";
                    }
                    else {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                }
                else {
                    echo "Этого логина нет в Вашем игноре<br/>";
                }
            }
            echo "<p><a href='?'>В список</a><br />";
            break;

        case 'del' :
            $req = mysql_query("SELECT * FROM `privat` WHERE `id`='" . $id . "' AND `me`='" . $login . "';");
            $res = mysql_fetch_array($req);
            if (mysql_num_rows($req) == 1) {
                mysql_query("DELETE FROM `privat` WHERE `id`='" . $id . "';");
                echo '<p>Юзер <b>' . $res['ignor'] . '</b> удалён из игнора</p>';
            }
            else {
                echo '<p>Ошибка</p>';
            }
            echo '<p><a href="?">Игнор-лист</a><br />';
            break;

        default :
            echo '<div class="phdr">Игнор-лист</div>';
            $ig = mysql_query("select * from `privat` where me='" . $login . "' and ignor!='';");
            $colig = mysql_num_rows($ig);
            while ($mass = mysql_fetch_array($ig)) {
                $uz = mysql_query("select * from `users` where name='$mass[ignor]';");
                $mass1 = mysql_fetch_array($uz);
                echo '<div class="menu">' . $mass['ignor'];
                $ontime = $mass1['lastdate'];
                $ontime2 = $ontime + 300;
                if ($realtime > $ontime2) {
                    echo '<font color="#FF0000"> [Off]</font>';
                }
                else {
                    echo '<font color="#00AA00"> [ON]</font>';
                }
                echo ' <a href="ignor.php?act=del&amp;id=' . $mass['id'] . '">[X]</a></div>';
            }
            echo '<p><a href="?act=add">Добавить юзера в игнор</a><br />';
            break;
    }
}

echo "<a href='../index.php?act=cab'>В кабинет</a></p>";
require_once ("../incfiles/end.php");

?>