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

defined('_IN_JOHNCMS') or die('Error:restricted access');

$agn = check($_SERVER['HTTP_USER_AGENT']);
if (!empty($_SESSION['uid']))
{
    if ($soft != $agn)
    {
        echo "Предупреждение безопасности:<br/> Ваш юзерагент<br/><font color='" . $clink . "'> $agn </font><br/>отличается от используемого ранее<br/> <font color='" . $clink . "'>$datauser[browser]</font>!<br/>";
        if ($dostmod == 1)
        {
            echo "<form action='" . $home . "/auto.php' method='get'>Имя:<br/><input type='text' name='n' maxlength='20' value='" . $login .
                "'/><br/>Пароль:<br/><input type='password' name='p' maxlength='20'/><br/><input type='checkbox' name='mem' value='1' checked='checked'/>Запомнить меня<br/><input type='submit' value='Вход'/></form>";
            mysql_query("update `users` set `browser`='" . $agn . "' where `id`='" . intval($_SESSION['uid']) . "';");
            setcookie('cuid', '');
            setcookie('cups', '');
            session_destroy();
            echo '<div style=\'text-align: center\'><a href=\'' . $home . '\'>&#169;' . $copyright . '</a></div></div></body></html>';
            exit;
        }
        mysql_query("update `users` set `browser`='" . $agn . "' where `id`='" . intval(check($_SESSION['uid'])) . "';");
    }
}


$rega = mysql_query("select * from `users` where preg='0' ;");
$rega1 = mysql_fetch_array($rega);
$rega2 = mysql_num_rows($rega);
if ($dostadm == "1" && $rega2 !== 0)
{
    echo "<div>Подтверждения регистрации ожидают
<a href=\"$home/$admp/preg.php\">$rega2</a>
человек</div>";
}


$ref = getenv("HTTP_REFERER");
$ref = htmlspecialchars($ref);

if ((isset($_SESSION['refsm'])) && ($headmod != "smile"))
{
    unset($_SESSION['refsm']);
}


?>