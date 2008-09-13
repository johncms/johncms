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

$textl = 'Вход';
$rootpath = '';
require_once ("incfiles/core.php");
require_once ("incfiles/head.php");

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
switch ($msg)
{
    case '1':
        echo "<br /><b>Вы не заполнили форму!</b>";
        break;

    case '2':
        echo "<br /><b>Ошибка авторизации!</b>";
        break;

    case '3':
        echo '<br /><b>Ваша заявка на регистрацию ещё не рассмотрена, ожидайте.</b><br /><br />';
        require_once ("incfiles/end.php");
        exit;

    case '4':
        $otkl = isset($_SESSION['otkl']) ? $_SESSION['otkl']:
        '';
        echo '<br /><b>Ваша заявка на регистрацию отклонена.</b><br />';
        echo 'Причина:<br />' . $otkl . '<br /><br />';
        require_once ("incfiles/end.php");
        exit;
}

echo "<div class = 'e' ><form action='auto.php' method='post'>
Имя:<br/>
<input type='text' name='n' maxlength='20'/><br/>
Пароль:<br/>
<input type='password' name='p' maxlength='20'/><br/>
<input type='checkbox' name='mem' value='1' checked='checked' />Запомнить меня<br/>
<input type='submit' value='Вход'/>
</form></div>";
echo "<br/><a href='str/skl.php?continue'>Забыли пароль?</a><br/><br/>";

require_once ("incfiles/end.php");

?>