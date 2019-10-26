<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

if ($id) {
    /** @var Psr\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\ToolsInterface $tools */
    $tools = $container->get(Johncms\Api\ToolsInterface::class);

    $error = false;

    // Скачивание прикрепленного файла Форума
    $req = $db->query("SELECT * FROM `cms_forum_files` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();

        if (file_exists(UPLOAD_PATH . 'forum/attach/' . $res['filename'])) {
            $dlcount = $res['dlcount'] + 1;
            $db->exec("UPDATE `cms_forum_files` SET  `dlcount` = '${dlcount}' WHERE `id` = '${id}'");
            header('location: ../upload/forum/attach/' . $res['filename']); //TODO: Разобраться со ссылкой
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }

    if ($error) {
        require 'system/head.php';
        echo $tools->displayError(_t('File does not exist'), '<a href="./">' . _t('Forum') . '</a>');
        require 'system/end.php';
        exit;
    }
} else {
    header('location: ./');
}
