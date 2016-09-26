<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (!$user_id || $rights < 6) {
    header("location: index.php");
    exit;
}

if (empty($_GET['id'])) {
    echo "ERROR<br><a href='index.php'>Back</a><br>";
    require_once('../incfiles/end.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$ms = $db->query("SELECT * FROM `gallery` WHERE `id` = " . $id)->fetch();

if ($ms['type'] != "al") {
    echo "ERROR<br><a href='index.php'>Back</a><br>";
    require_once('../incfiles/end.php');
    exit;
}

$rz1 = $db->query("SELECT * FROM `gallery` WHERE type='rz' AND id='" . $ms['refid'] . "'")->fetch();

if ((!empty($_SESSION['uid']) && $rz1['user'] == 1 && $ms['text'] == $login) || $rights >= 6) {
    $dopras = [
        "gif",
        "jpg",
        "png",
    ];
    $tff = implode(" ,", $dopras);
    $fotsize = $set['flsz'] / 5;
    echo '<h3>' . $lng_gal['upload_photo'] . "</h3>" . $lng_gal['allowed_types'] . ": $tff<br>" . $lng_gal['maximum_weight'] . ": $fotsize кб.<br><form action='index.php?act=load&amp;id=" . $id .
        "' method='post' enctype='multipart/form-data'><p>" . $lng_gal['select_photo'] . ":<br><input type='file' name='fail'/></p><p>" . $lng['description'] . ":<br><textarea name='text'></textarea></p><p><input type='submit' value='" . $lng['sent'] . "'/></p></form><a href='index.php?id="
        . $id . "'>" . _t('Back') . "</a>";
} else {
    header("location: index.php");
}
