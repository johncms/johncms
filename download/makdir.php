<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once("../incfiles/head.php");

if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $cat = isset($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;

    if (isset ($_POST['submit'])) {
        if (empty ($cat)) {
            $droot = $loadroot;
        } else {
            provcat($cat);
            $adrdir = $db->query("SELECT * FROM `download` WHERE type = 'cat' AND id = '" . $cat . "'")->fetch();
            $droot = "$adrdir[adres]/$adrdir[name]";
        }

        $drn = trim($_POST['drn']);
        $rusn = trim($_POST['rusn']);
        $mk = mkdir("$droot/$drn", 0777);

        if ($mk == true) {
            chmod("$droot/$drn", 0777);
            echo "Папка создана<br/>";

            $db->exec("INSERT INTO `download` SET
              `refid` = $cat,
              `adres` = " . $db->quote($droot) . ",
              `time` = " . time() . ",
              `name` = " . $db->quote($drn) . ",
              `type` = 'cat',
              `ip` = '',
              `soft` = '',
              `text` = " . $db->quote($rusn) . ",
              `screen` = ''
              ");

            $newcat = $db->query("select * from `download` where type = 'cat' and name=" . $db->quote($drn) . " and refid = '" . $cat . "'")->fetch();
            echo "&#187;<a href='?cat=" . $newcat['id'] . "'>В папку</a><br/>";
        } else {
            echo "ERROR<br/>";
        }
    } else {
        echo "<form action='?act=makdir&amp;cat=" . intval($_GET['cat']) . "' method='post'>
         <p>" . $lng_dl['folder_name'] . "<br />
         <input type='text' name='drn'/></p>
         <p>" . $lng_dl['folder_name_for_list'] . ":<br/>
         <input type='text' name='rusn'/></p>
         <p><input type='submit' name='submit' value='Создать'/></p>
         </form>";
    }
}

echo "<a href='?'>" . $lng['back'] . "</a><br/>";
