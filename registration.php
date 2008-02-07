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

$textl = 'Регистрация';
require ("incfiles/db.php");
require ("incfiles/func.php");
require ("incfiles/data.php");
require ("incfiles/head.php");
require ("incfiles/inc.php");
require ("incfiles/char.php");
###########
$delimag = opendir("gallery/temp");
while ($imd = readdir($delimag))
{
    if ($imd != "." && $imd != ".." && $imd != "index.php")
    {
        $im[] = $imd;
    }
}
closedir($delimag);
$totalim = count($im);
for ($imi = 0; $imi < $totalim; $imi++)
{
    $filtime[$imi] = filemtime("gallery/temp/$im[$imi]");
    $tim = time();
    $ftime1 = $tim - 10;
    if ($filtime[$imi] < $ftime1)
    {
        unlink("gallery/temp/$im[$imi]");
    }
}
function regform()
{
    $cod = rand(1000, 9999);
    $_SESSION['code'] = $cod;
    echo "* - Заполнить обязательно<br/><form action='registration.php' method='post'>Логин:*<br/><input type='text' name='name' maxlength='20' value='" . check($_POST['name']) .
        "' /><br/>Пароль:*<br/><input type='text' name='password' maxlength='20' /><br/>Имя:*<br/><input type='text' name='imname' maxlength='20' value='" . check($_POST['imname']) .
        "' /><br/>Пол:<br/><select name='sex'><option value='m'>Муж.</option><option value='zh'>Жен.</option></select><br/>О себе*:<br/><textarea rows='3' name='about'>" . check($_POST['about']) . "</textarea><br/><br/>";
    echo "<div class='b'>Если Вы не видите рисунок с кодом,включите поддержку графики в настройках браузера и обновите страницу.<br/>";
    $imwidth = 85;
    $imheight = 26;
    $im = ImageCreate($imwidth, $imheight);
    $background_color = ImageColorAllocate($im, 255, 255, 255);
    $text_color = ImageColorAllocate($im, 0, 0, 0);
    $border_color = ImageColorAllocate($im, 154, 154, 154);
    $g1 = imagecolorallocate($im, 152, 152, 152);
    for ($i = 0; $i <= 100; $i += 6)
        imageline($im, $i, 0, $i, 25, $g1);
    for ($i = 0; $i <= 25; $i += 5)
        imageline($im, 0, $i, 100, $i, $g1);

    $x = 0;
    $stringlength = strlen($cod);
    for ($i = 0; $i < $stringlength; $i++)
    {
        $x = $x + (rand(8, 21));
        $y = rand(2, 10);
        $font = rand(4, 25);
        $single_char = substr($cod, $i, 1);
        imagechar($im, $font, $x, $y, $single_char, $text_color);
    }
    $tm = time();
    $imagnam = "gallery/temp/$tm.gif";
    ImageGif($im, $imagnam, 100);
    echo "<img src='" . $imagnam . "' alt=''/><br/>";
    imagedestroy($im);
    echo "<br/></div>";
    echo "Код с картинки:<br/><input type='text' maxlength='4'  name='kod'/><br/><input type='submit' name='submit' value='Зарегистрироваться'/></form>";
}

if (isset($_POST['submit']))
{
    $kod = intval($_POST['kod']);
    $name = check(trim($_POST['name']));
    $name = utfwin($name);
    $name = substr($name, 0, 15);
    $name = winutf($name);
    $imname = check(trim($_POST['imname']));
    $imname = utfwin($imname);
    $imname = substr($imname, 0, 15);
    $imname = winutf($imname);
    $password = check(trim($_POST['password']));
    $password = substr($password, 0, 20);
    $about = check(trim($_POST['about']));
    $about = utfwin($about);
    $about = substr($about, 0, 500);
    $about = winutf($about);
    $sex = check(trim($_POST['sex']));
    $par = md5($password);
    $q = mysql_query("select * from `users` where name='" . $name . "';");
    $us = mysql_fetch_array($q);
    if (empty($about))
        $error = 'Не указана инфа о себе!<br/>';
    if (empty($name))
        $error = $error . 'Не введён логин!<br/>';
    if (empty($password))
        $error = $error . 'Не введён пароль!<br/>';
    if (empty($imname))
        $error = $error . 'Не введено имя!<br/>';
    if (empty($kod))
        $error = $error . 'Не введён проверочный код!<br/>';
    if (!empty($kod) && $kod != $_SESSION['code'])
        $error = $error . 'Проверочный код неверен!<br/>';
    if (preg_match("/[^\da-zA-Z_]+/", $password))
        $error = $error . 'В пароле есть запрещающие знаки!<br/>';
    $iml = $us[name];
    if (empty($error))
    {
        if (strtolower($name) != strtolower($iml))
        {
            if ($rmod != 1)
            {
                $preg = 1;
            } else
            {
                $preg = 0;
            }
            mysql_query("insert into `users` values(0,'" . $name . "','" . md5($par) . "','" . $imname . "','" . $sex . "','0','0','0','0','','" . $realtime . "','','','','','" . $about . "','','','0','','" . $ipp . "','" . $agn .
                "','20','20','','','','','','0','','','','','" . $preg . "','','','0','0','0','0','','','0','','','','0','','','','0','0','0','0','0','0','0','','0','','','','','','','','','','','','','','0','0','20','','0','','0','0','');");
            $idq = mysql_query("select * from `users` where name='" . $name . "';");
            $usid = mysql_fetch_array($idq);
            $usid1 = $usid['id'];
            echo "Вы зарегистрированы!<br/>";
            echo "Ваш id: " . $usid1 . "<br/>";
            echo "Ваш логин: " . $name . "<br/>";
            echo "Ваш Пароль: " . $password . "<br/>";
            echo "Ссылка для автовхода:<br/><input type='text' value='" . $home . "/auto.php?n=" . $name . "&amp;p=" . $password . "' /><br/>";

            if ($rmod == 1)
            {
                echo "Пожалуйста,ожидайте подтверждения Вашей регистрации администратором<br/>";
            } else
            {
                echo "<a href='auto.php?n=" . $name . "&amp;p=" . $password . "'>ВХОД</a><br/>";
            }
        } else
        {
            echo "Пользователь с таким логином зарегистрирован!<br/><a href='registration.php'>Назад</a><br/>";
        }
    } else
    {
        print "$error<br/>";
        regform();
    }
} else
{
    if ($rmod == 1)
    {
        echo "Внимание!В данный момент на сайте включена премодерация регистрации.<br/>Вы сможете получить авторизованный доступ к разделам сайта после подтверждения Вашей регистрации администратором.<br/>";
    }
    regform();
}
require ("incfiles/end.php");

?>