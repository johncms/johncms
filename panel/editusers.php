<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
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
session_name("SESID");
session_start();
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");


if ($dostadm == 1)
{
    if (empty($_GET['user']))
    {
        echo "Вы не ввели логин!<br/><a href='main.php'>В админку</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }


    $qus = @mysql_query("select * from `users` where id='" . check(intval($_GET['user'])) . "';");
    $userprof = @mysql_fetch_array($qus);
    $nam = trim($userprof['name']);
    ##########

    if (($login !== $nickadmina) && ($nam == $nickadmina) || ($nam !== $login) && ($nickadmina !== $login) && ($statad == "7") && ($userprof['rights'] == "7"))
    {
        echo "У ВАС НЕДОСТАТОЧНО ПРАВ ДЛЯ ЭТОГО!<br/>";
        require ("../incfiles/end.php");
        exit;
    }

    if ($nam == $login)
    {
        echo 'ВНИМАНИЕ! ВЫ РЕДАКТИРУЕТЕ CОБСТВЕННЫЙ АККАУНТ<br/>';
    }
    ################


    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "del":
            $user = intval(check($_GET['user']));

            $q1 = mysql_query("select * from `users` where id='" . $user . "';");
            $arr1 = mysql_fetch_array($q1);


            if ($arr1['name'] != $nickadmina && $arr1['name'] != $nickadmina2)
            {
                if (isset($_GET['yes']))
                {
                    mysql_query("delete from `users` where id='" . $user . "' LIMIT 1;");
                    echo "Пользователь $arr1[name] удалён!<br/>";
                    echo "<a href='main.php'>В админку</a><br/>";
                } else
                {
                    echo "Вы уверены в удалении юзера $arr1[name]?<br/><a href='editusers.php?act=del&amp;user=" . $user . "&amp;yes'>Да</a> | <a href='../str/anketa.php?user=" . $user . "'>Нет</a><br/>";
                }
            } else
            {
                echo "Нельзя!!!!!<br/>";
            }
            break;


        case "edit":


            $user = intval(check($_GET['user']));

            $q1 = @mysql_query("select * from `users` where id='" . $user . "';");
            $arr1 = @mysql_fetch_array($q1);
            echo "Профиль юзера $arr1[name]<br/>";
            $usdata = array("name" => "Логин:", "par" => "ПАРОЛЬ:", "imname" => "Имя:", "status" => "Статус:", "live" => "Город:", "mibile" => "Мобила:", "mail" => "E-mail:", "icq" => "ICQ:", "www" => "Сайт:", "about" => "О себе:");
            if (isset($_GET['ok']))
            {
                echo "Профиль изменён!<br/>";
            }

            echo "<form action='editusers.php?act=yes&amp;user=" . intval(check($_GET['user'])) . "' method='post' >";

            foreach ($usdata as $key => $value)
            {
                echo "$usdata[$key]<br/><input type='text' name='" . $key . "' value='" . $userprof[$key] . "'/><br/>";
            }

            if ($userprof[sex] == "m")
            {
                echo 'Пол:<br/><select name=\'sex\' title=\'Пол\' value=\'' . $userprof['sex'] . '\'>' . '<option value=\'m\'>М</option>' . '<option value=\'zh\'>Ж</option></select><br/>';
            } else
            {


                echo 'Пол:<br/><select name=\'sex\' title=\'Пол\' value=\'' . $userprof['sex'] . '\'>' . '<option value=\'zh\'>Ж</option>' . '<option value=\'m\'>М</option></select><br/>';
            }


            echo "Назначить:<br/>";
            if ($userprof['rights'] == "1")
            {
                echo "<input name='admst' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='1'/>";
            }
            echo "киллером<br/>";
            if ($userprof['rights'] == "2")
            {
                echo "<input name='admst' type='radio' value='2' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='2'/>";
            }
            echo "модером чата<br/>";
            if ($userprof['rights'] == "3")
            {
                echo "<input name='admst' type='radio' value='3' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='3'/>";
            }
            echo "модером форума<br/>";
            if ($userprof['rights'] == "4")
            {
                echo "<input name='admst' type='radio' value='4' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='4'/>";
            }
            echo "зам. админа по загрузкам<br/>";

            if ($userprof['rights'] == "5")
            {
                echo "<input name='admst' type='radio' value='5' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='5'/>";
            }
            echo "зам. админа по библиотеке<br/>";

            if ($userprof['rights'] == "6")
            {
                echo "<input name='admst' type='radio' value='6' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='6'/>";
            }
            echo "супермодератором<br/>";
            if ($dostsadm == 1)
            {
                if ($userprof['rights'] == "7")
                {
                    echo "<input name='admst' type='radio' value='7' checked='checked' />";
                } else
                {
                    echo "<input name='admst' type='radio' value='7'/>";
                }
                echo "администратором<br/>";
            } else
            {
                if ($dostadm == 1 && $nam == $login)
                {
                    echo "<input name='admst' type='hidden' value='7'/>";
                }
            }
            if ($userprof['rights'] == "0")
            {
                echo "<input name='admst' type='radio' value='0' checked='checked' />";
            } else
            {
                echo "<input name='admst' type='radio' value='0'/>";
            }
            echo "Нулевой доступ<br/>";

            print '<input type=\'submit\' value=\'ok\'/></form>';
            echo "<a href='main.php'>В админку</a><br/>";
            break;

        case "yes":
            if (!empty($_POST['par']))
            {
                $par = check($_POST['par']);
                $par1 = md5(md5($par));
            } else
            {
                $par1 = $userprof[password];
            }
            $status = check($_POST['status']);
            $name = check($_POST['name']);
            $imname = check($_POST['imname']);
            $sex = check($_POST['sex']);
            $mibile = check($_POST['mibile']);
            $mail = mysql_escape_string(htmlspecialchars($_POST['mail']));
            $icq = intval(check($_POST['icq']));
            $www = check($_POST['www']);
            $about = check($_POST['about']);
            $live = check($_POST['live']);
            $rights = intval(check($_POST['admst']));

            mysql_query("update `users` set name='" . $name . "', password='" . $par1 . "', imname='" . $imname . "', sex='" . $sex . "', mibile='" . $mibile . "', mail='" . $mail . "',rights='" . $rights . "', icq='" . $icq . "', www='" . $www .
                "', about='" . $about . "', live='" . $live . "', status='" . $status . "'  where id='" . intval(check($_GET['user'])) . "';");
            if (!empty($_POST['par']))
            {
                echo "Вы изменили пароль юзера!<br/>Новый пароль: $par<br/>";
                echo "<a href='main.php'>В админку</a><br/>";
            } else
            {
                echo "Профиль изменён<br/><a href='main.php'>В админку</a><br/>";
            }

            break;
    }
} else
{
    header("Location: ../index.php?err");
}


require ("../incfiles/end.php");
?>

