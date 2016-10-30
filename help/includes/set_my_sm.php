<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Настраиваем список своих смайлов
$adm = isset($_GET['adm']);
$add = isset($_POST['add']);
$delete = isset($_POST['delete']);
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

/** @var Johncms\Tools $tools */
$tools = App::getContainer()->get('tools');

if (($adm && !$rights) || ($add && !$adm && !$cat) || ($delete && !$_POST['delete_sm']) || ($add && !$_POST['add_sm'])) {
    echo $tools->displayError($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['smileys'] . '</a>');
    require('../system/end.php');
    exit;
}

$smileys = unserialize($datauser['smileys']);

if (!is_array($smileys)) {
    $smileys = [];
}

if ($delete) {
    $smileys = array_diff($smileys, $_POST['delete_sm']);
}

if ($add) {
    $add_sm = $_POST['add_sm'];
    $smileys = array_unique(array_merge($smileys, $add_sm));
}

if (count($smileys) > $user_smileys) {
    $smileys = array_chunk($smileys, $user_smileys, true);
    $smileys = $smileys[0];
}

$db->query("UPDATE `users` SET `smileys` = " . $db->quote(serialize($smileys)) . " WHERE `id` = '$user_id'");

if ($delete || isset($_GET['clean'])) {
    header('location: index.php?act=my_smilies&start=' . $start . '');
} else {
    header('location: index.php?act=' . ($adm ? 'admsmilies' : 'usersmilies&cat=' . urlencode($cat) . '') . '&start=' . $start . '');
}
