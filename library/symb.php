<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (isset ($_POST['submit'])) {
    if (!empty ($_POST['simvol'])) {
        $simvol = intval($_POST['simvol']);
    }
    $_SESSION['symb'] = $simvol;
    echo "На время текущей сессии <br/>принято количество символов на страницу: $simvol <br/>";
}
else {
    echo "<form action='?act=symb' method='post'>
	Выберите количество символов на страницу:<br/><select name='simvol'>";
    if (!empty ($_SESSION['symb'])) {
        $realr = $_SESSION['symb'];
        echo "<option value='" . $realr . "'>" . $realr . "</option>";
    }
    echo
    "<option value='500'>500</option>
<option value='1000'>1000</option>
<option value='2000'>2000</option>
<option value='3000'>3000</option>
<option value='4000'>4000</option>
	</select><br/>
<input type='submit' name='submit' value='ok'/></form>";
}
echo "&#187;<a href='?'>К категориям</a><br/>";

?>