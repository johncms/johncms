<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

/**
 * @var Johncms\System\Config\Config $config
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (
    ! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name']) || (pathinfo(
        $res_down['name'],
        PATHINFO_EXTENSION
    ) != 'jar' && ! isset($_GET['more'])) || ($res_down['type'] == 3 && $user->rights < 6 && $user->rights != 4)
) {
    echo _t('File not found') . ' <a href="?">' . _t('Downloads') . '</a>';
    exit;
}

if (isset($_GET['more'])) {
    $more = abs((int) ($_GET['more']));
    $req_more = $db->query("SELECT * FROM `download__more` WHERE `id` = '${more}' LIMIT 1");
    $res_more = $req_more->fetch();
    if (
        ! $req_more->rowCount() || ! is_file($res_down['dir'] . '/' . $res_more['name']) || pathinfo(
            $res_more['name'],
            PATHINFO_EXTENSION
        ) != 'jar'
    ) {
        echo _t('File not found') . '<a href="?">' . _t('Downloads') . '</a>';
        exit;
    }
    $down_file = $res_down['dir'] . '/' . $res_more['name'];
    $jar_file = $res_more['name'];
} else {
    $down_file = $res_down['dir'] . '/' . $res_down['name'];
    $jar_file = $res_down['name'];
}

if (! isset($_SESSION['down_' . $id])) {
    $db->exec('UPDATE `download__files` SET `field`=`field`+1 WHERE `id`=' . $id);
    $_SESSION['down_' . $id] = 1;
}

$size = filesize($down_file);
require SYSPATH . 'lib/pclzip.lib.php';
$zip = new PclZip($down_file);
$content = $zip->extract(PCLZIP_OPT_BY_NAME, 'META-INF/MANIFEST.MF', PCLZIP_OPT_EXTRACT_AS_STRING);

$out = $content[0]['content'] . "\n" . 'MIDlet-Jar-Size: ' . $size . "\n" . 'MIDlet-Jar-URL: ' . $config['homeurl'] . $res_down['dir'] . '/' . $jar_file;
Functions::downloadFile($out, basename($down_file) . '.jad');
