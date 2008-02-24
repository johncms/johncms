<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);
session_name('SESID');
session_start();
$headmod = 'contacts';
$textl = 'Контакты';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
if (!empty($_SESSION['pid']))
{
    if (!empty($_GET['act']))
    {
        $act = $_GET['act'];
    }
    switch ($act)
    {
        case "trans":
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;
            ############################
        case "add":
            echo "<form action='cont.php?act=edit&amp;add=1' method='post'>
	 Введите ник<br/>";
            echo "<input type='text' name='nik' value='' /><br/>
 <input type='submit' value='Добавить' />  
  </form>";
            echo "<a href='?'>В список</a><br/>";
            break;
        case "edit":
            if (!empty($_POST['nik']))
            {
                $nik = check($_POST['nik']);
            } elseif (!empty($_GET['nik']))
            {
                $nik = check($_GET['nik']);
            } else
            {
                if (empty($_GET['id']))
                {
                    echo "Ошибка!<br/><a href='cont.php'>В контакты</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }

                $id = intval(check(trim($_GET['id'])));
                $nk = mysql_query("select * from `users` where id='" . $id . "';");
                $nk1 = mysql_fetch_array($nk);
                $nik = $nk1[name];
            }
            if (!empty($_GET['add']))
            {
                $add = intval($_GET['add']);
            }
            $adс = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $nik . "';");

            $adc1 = mysql_num_rows($adс);
            $addc = mysql_query("select * from `users` where name='" . $nik . "';");
            $addc1 = mysql_num_rows($addc);
            if ($add == 1)
            {
                if ($adc1 == 0)
                {
                    if ($addc1 == 1)
                    {
                        mysql_query("insert into `privat` values(0,'" . $foruser . "','','" . $realtime . "','','','','','0','" . $login . "','" . $nik . "','','');");
                        echo "Контакт добавлен<br/>";
                    } else
                    {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                } else
                {
                    echo "Данный логин уже есть в Ваших контактах<br/>";
                }
            } else
            {
                if ($adc1 == 1)
                {
                    if ($addc1 == 1)
                    {

                        mysql_query("delete from `privat` where me='" . $login . "' and cont='" . $nik . "';");
                        echo "Контакт удалён<br/>";
                    } else
                    {
                        echo "Данный логин отсутствует в базе данных<br/>";
                    }
                } else
                {
                    echo "Этого логина нет в Ваших контактах<br/>";
                }
            }
            echo "<a href='?'>В контакты</a><br />";

            break;


        case "write":
            if (!empty($_GET['user']))
            {
                $messages = mysql_query("select * from `users` where id='" . intval($_GET['user']) . "';");
                $userr = mysql_fetch_array($messages);
                $adresat = $userr['name'];
                $contime = mysql_query("select * from `privat` where me='$login' and cont='" . $userr['name'] . "';");
                $ctim = mysql_fetch_array($contime);
                $dtime = date("d.m.Y / H:i", $ctim['time']);
                echo "<div>";

                echo "<form action='pradd.php?act=send' method='post' enctype='multipart/form-data'>
	 Для: $adresat<br/>";
                echo "Имя: $userr[imname]<br/>";
                if ($userr['sex'] == "m")
                {
                    echo "Парень<br/>";
                }
                if ($userr['sex'] == "zh")
                {
                    echo "Девушка<br/>";
                }
                echo "Дата добавления: $dtime<br/>";
                if ($userr[mailact] == 1)
                {
                    if (!empty($userr[icq]))
                    {
                        echo '<img src="http://web.icq.com/whitepages/online?icq=' . $userr[icq] . '&amp;img=5" alt=""/> ICQ:' . $userr[icq] . ' <br/> ';
                    }
                    if (!empty($userr['mail']))
                    {
                        echo "E-mail:";
                        if ($userr[mailvis] == 1)
                        {
                            echo "$userr[mail]<br/>";
                        } else
                        {
                            echo "скрыт<br/>";
                        }
                    }
                }
                if (!empty($userr[www]) && $userr[www] !== "http://" && stristr($userr[www], "http://"))
                {
                    echo "Сайт: <a href='" . $userr['www'] . "'>" . $userr['www'] . "</a><br/>";
                }


                echo "<input type='hidden' name='foruser' value='" . $adresat . "' />";
                echo " <br />Тема:<br />
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
                echo "<a href='cont.php?act=trans'>Транслит</a><br /><a href='smile.php'>Смайлы</a><br /><a href='?'>В список</a></div>";
            } else
            {
                echo "Ошибка-не указан адресат<br/>";
            }
            break;


        default:
            $contacts = mysql_query("select * from `privat` where me='$login' and cont!='';");
            $colcon = mysql_num_rows($contacts);
            while ($mass = mysql_fetch_array($contacts))
            {
                $uz = mysql_query("select * from `users` where name='$mass[cont]';");
                $mass1 = mysql_fetch_array($uz);
                echo "<a href='?act=write&amp;user=" . $mass1[id] . "'>$mass[cont]</a>";
                $ontime = $mass1[pvrem];
                $ontime2 = $ontime + 300;
                if ($realtime > $ontime2)
                {
                    echo " [Off]<br/>";
                } else
                {
                    echo " [ON]<br/>";
                }
            }

            echo "<hr /><a href='?act=add'>Добавить контакт</a><br />";
            break;
    }
}
echo "<a href='privat.php?'>В приват</a><br />";
require ("../incfiles/end.php");
?>