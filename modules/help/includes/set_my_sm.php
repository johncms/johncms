<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Настраиваем список своих смайлов
$adm = isset($_GET['adm']);
$add = isset($_POST['add']);
$delete = isset($_POST['delete']);
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

if (($adm && ! $user->rights) || ($add && ! $adm && ! $cat) || ($delete && ! $_POST['delete_sm']) || ($add && ! $_POST['add_sm'])) {
    header('location: ?act=my_smilies');
    exit;
}

$smileys = unserialize($user->smileys, ['allowed_classes' => false]);

if (! is_array($smileys)) {
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

$db->query('UPDATE `users` SET `smileys` = ' . $db->quote(serialize($smileys)) . ' WHERE `id` = ' . $user->id);

if ($delete || isset($_GET['clean'])) {
    header('location: ?act=my_smilies&start=' . $start . '');
} else {
    header('location: ?act=' . ($adm ? 'admsmilies' : 'usersmilies&cat=' . urlencode($cat) . '') . '&start=' . $start . '');
}
