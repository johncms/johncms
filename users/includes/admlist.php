<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = $lng['administration'];
$headmod = "admlist";
require('../incfiles/head.php');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

/*
-----------------------------------------------------------------
Выводим список администрации
-----------------------------------------------------------------
*/
echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['administration'] . '</div>';
$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= 1")->fetchColumn();
$req = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `rights` >= 1 ORDER BY `rights` DESC LIMIT $start, $kmess");

for ($i = 0; $res = $req->fetch(); ++$i) {
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo functions::display_user($res) . '</div>';
}

echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<p>' . functions::display_pagination('index.php?act=admlist&amp;', $start, $total, $kmess) . '</p>' .
        '<p><form action="index.php?act=admlist" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo'<p><a href="index.php?act=search">' . $lng['search_user'] . '</a><br />' .
    '<a href="index.php">' . $lng['back'] . '</a></p>';
