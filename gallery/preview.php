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

if (isset ($_POST['submit'])) {
    if (!empty ($_POST['razmer'])) {
        $razmer = intval($_POST['razmer']);
    }
    $_SESSION['frazm'] = $razmer;
    echo "На время текущей сессии <br/>принят максимальный размер изображений <br/>при просмотре $razmer*$razmer px<br/>";
}
else {
    echo "<form action='index.php?act=preview' method='post'>
	Выберите размеры просмотра картинок:<br/><select name='razmer'>";
    if (!empty ($_SESSION['frazm'])) {
        $realr = $_SESSION['frazm'];
        echo "<option value='" . $realr . "'>" . $realr . "*" . $realr . "</option>";
    }
    echo
    "<option value='32'>32*32</option>
<option value='50'>50*50</option>
<option value='64'>64*64</option>
<option value='80'>80*80</option>
<option value='100'>100*100</option>
<option value='120'>120*120</option>
<option value='160'>160*160</option>
<option value='200'>200*200</option>
	</select><br/>
<input type='submit' name='submit' value='ok'/></form>";
}
echo "<a href='index.php?'>В галерею</a><br/>";

?>