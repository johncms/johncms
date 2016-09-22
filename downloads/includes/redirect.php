<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$id = isset($_POST['admin_id']) ? abs(intval($_POST['admin_id'])) : false;
$act = isset($_POST['admin_act']) ? trim($_POST['admin_act']) : '';

if ($act == 'clean') {
    header('Location: ?act=scan_dir&do=clean&id=' . $id);
} else {
    header('Location: ?act=' . $act . '&id=' . $id);
}
