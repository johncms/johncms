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
 */

if (empty($_GET['n'])) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Download topic'),
            'page_title'    => __('Download topic'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$n = trim($_GET['n']);
$o = opendir(UPLOAD_PATH . 'forum/topics');

while ($f = readdir($o)) {
    if ($f != '.' && $f != '..' && $f != 'index.php' && $f != '.htaccess') {
        $ff = pathinfo($f, PATHINFO_EXTENSION);
        $f1 = str_replace(".${ff}", '', $f);
        $a[] = $f;
        $b[] = $f1;
    }
}

$tt = count($a);

if (! in_array($n, $b)) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Download topic'),
            'page_title'    => __('Download topic'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

for ($i = 0; $i < $tt; $i++) {
    $tf = pathinfo($a[$i], PATHINFO_EXTENSION);
    $tf1 = str_replace(".${tf}", '', $a[$i]);
    if ($n == $tf1) {
        header("Location: ../upload/forum/topics/${n}.${tf}"); //TODO: Разобраться с путем
        exit;
    }
}
