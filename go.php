<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

define('_IN_JOHNCMS', 1);

require 'system/bootstrap.php';

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

if ($id) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Редирект по рекламной ссылке
    $req = $db->query("SELECT * FROM `cms_ads` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $count_link = $res['count'] + 1;
        $db->exec("UPDATE `cms_ads` SET `count` = '${count_link}'  WHERE `id` = '${id}'");
        header('Location: ' . $res['link']);
    } else {
        header('Location:' . $config->homeurl . '/404/');
    }
}
