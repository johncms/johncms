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
require_once('../incfiles/head.php');
if ($rights == 4 || $rights >= 6) {
    if ($_GET['file'] == "") {
        echo functions::display_error($lng_dl['file_not_selected'], '<a href="index.php">' . $lng['back'] . '</a>');
        require_once('../incfiles/end.php');
        exit;
    }
    $file = intval(trim($_GET['file']));
    $file1 = mysql_query("select * from `download` where type = 'file' and id = '" . $file . "';");
    $file2 = mysql_num_rows($file1);
    $adrfile = mysql_fetch_array($file1);
    if (($file1 == 0) || (!is_file("$adrfile[adres]/$adrfile[name]"))) {
        echo functions::display_error($lng_dl['file_select_error'], '<a href="index.php">' . $lng['back'] . '</a>');
        require_once('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $scrname = $_FILES['screens']['name'];
        $scrsize = $_FILES['screens']['size'];
        $scsize = GetImageSize($_FILES['screens']['tmp_name']);
        $scwidth = $scsize[0];
        $scheight = $scsize[1];
        $ffot = strtolower($scrname);
        $dopras = array (
            "gif",
            "jpg",
            "png"
        );
        if ($scrname != "") {
            $formfot = functions::format($ffot);
            if (!in_array($formfot, $dopras)) {
                echo $lng_dl['screenshot_upload_error'] . '<br/><a href="index.php?act=screen&amp;file=' . $file . '">' . $lng['repeat'] . '</a><br/>';
                require_once('../incfiles/end.php');
                exit;
            }
            if ($scwidth > 320 || $scheight > 320) {
                echo $lng_dl['screenshot_size_error'] . '<br/><a href="index.php?act=screen&amp;file=' . $file . '">' . $lng['repeat'] . '</a><br/>';
                require_once('../incfiles/end.php');
                exit;
            }
            if (preg_match("/[^\da-z_\-.]+/", $scrname)) {
                echo $lng_dl['screenshot_name_error'] . "<br/><a href='?act=screen&amp;file=" . $file . "'>" . $lng['repeat'] . "</a><br/>";
                require_once('../incfiles/end.php');
                exit;
            }
            $filnam = "$adrfile[name]";
            unlink("$screenroot/$adrfile[screen]");
            if ((move_uploaded_file($_FILES["screens"]["tmp_name"], "$screenroot/$filnam.$formfot")) == true) {
                $ch1 = "$filnam.$formfot";
                @chmod("$ch1", 0777);
                @chmod("$screenroot/$ch1", 0777);
                echo $lng_dl['screenshot_uploaded'] . '<br/>';
                mysql_query("update `download` set screen='" . $ch1 . "' where id='" . $file . "';");
            }
        }
    } else {
        echo $lng_dl['upload_screenshot'] . '<br/>';
        echo '<form action="index.php?act=screen&amp;file=' . $file . '" method="post" enctype="multipart/form-data"><p>' . $lng['select'] . ' (max. 320*320):<br/>' .
            '<input type="file" name="screens"/>' .
            '</p><p><input type="submit" name="submit" value="' . $lng_dl['upload'] . '"/></p>' .
            '</form>';
    }
}
echo '<p><a href="index.php?act=view&amp;file=' . $file . '">' . $lng['back'] . '</a></p>';
?>