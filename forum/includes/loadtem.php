<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (empty($_GET['n'])) {
    require('../incfiles/head.php');
    echo functions::display_error(_t('Wrong data'));
    require('../incfiles/end.php');
    exit;
}

$n = trim($_GET['n']);
$o = opendir("../files/forum/topics");

while ($f = readdir($o)) {
    if ($f != "." && $f != ".." && $f != "index.php" && $f != ".htaccess") {
        $ff = pathinfo($f, PATHINFO_EXTENSION);
        $f1 = str_replace(".$ff", "", $f);
        $a[] = $f;
        $b[] = $f1;
    }
}

$tt = count($a);

if (!in_array($n, $b)) {
    require_once('../incfiles/head.php');
    echo functions::display_error(_t('Wrong data'));
    require_once('../incfiles/end.php');
    exit;
}

for ($i = 0; $i < $tt; $i++) {
    $tf = pathinfo($a[$i], PATHINFO_EXTENSION);
    $tf1 = str_replace(".$tf", "", $a[$i]);
    if ($n == $tf1) {
        header("Location: ../files/forum/topics/$n.$tf");
    }
}
