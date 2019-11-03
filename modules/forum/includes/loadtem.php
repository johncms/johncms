<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (empty($_GET['n'])) {
    echo $tools->displayError(_t('Wrong data'));
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
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
    echo $tools->displayError(_t('Wrong data'));
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

for ($i = 0; $i < $tt; $i++) {
    $tf = pathinfo($a[$i], PATHINFO_EXTENSION);
    $tf1 = str_replace(".${tf}", '', $a[$i]);
    if ($n == $tf1) {
        header("Location: ../upload/forum/topics/${n}.${tf}"); //TODO: Разобраться с путем
    }
}
