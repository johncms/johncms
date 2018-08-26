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

require_once ("../incfiles/head.php");
if (isset ($_POST['submit'])) {
    if (!empty ($_POST['razmer'])) {
        $razmer = intval($_POST['razmer']);
    }
    $_SESSION['razm'] = $razmer;
    echo $lng_dl['preview_size_set'] . " $razmer*$razmer px<br/>";
}
else {
    echo "<form action='?act=preview' method='post'><p>
	" . $lng_dl['select_preview_size'] . ":<br/><select name='razmer'>";
    if (!empty ($_SESSION['razm'])) {
        $realr = $_SESSION['razm'];
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
</select><input type='submit' name='submit' value='ok'/></p></form>";
}
echo "<p><a href='?'>" . $lng['back'] . "</a></p>";

?>