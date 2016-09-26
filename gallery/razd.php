<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 6) {
    if (isset($_POST['submit'])) {
        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);
        $user = 0;
        $text = isset($_POST['text']) ? trim($_POST['text']) : '';
        $db->query("INSERT INTO `gallery` VALUES(0,'0','" . time() . "','rz',''," . $db->quote($text) . ",'','" . $user . "','','');");
        header("location: index.php");
    } else {
        echo '<div class="phdr"><b>' . $lng_gal['create_section'] . '</b></div>' .
            '<form action="index.php?act=razd" method="post">' .
            '<div class="gmenu">' .
            '<p>' . $lng['name'] . ':<br><input type="text" name="text"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
    }
}
