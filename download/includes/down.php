<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($id) {
    $stmt = $db->query("select * from `download` where `id` = '$id' LIMIT 1");
    if ($stmt->rowCount()) {
        $mas = $stmt->fetch();
        if (!empty($mas['name'])) {
            if (file_exists($mas['adres'] . '/' . $mas['name'])) {
                $sc = $mas['ip'] + 1;
                $db->exec("update `download` set `ip` = '$sc' where `id` = '$id' LIMIT 1");
                $_SESSION['upl'] = '';
                header('location: ' . $mas['adres'] . '/' . $mas['name']); exit;
            }
        }
    }
}
require_once ('../incfiles/head.php');
echo "ERROR<br/><a href='index.php'>Back</a><br/>";