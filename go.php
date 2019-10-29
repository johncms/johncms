<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Zend\I18n\Translator\Translator;

define('_IN_JOHNCMS', 1);

require 'system/bootstrap.php';

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config->homeurl;
$url = isset($_REQUEST['url']) ? strip_tags(rawurldecode(trim($_REQUEST['url']))) : false;

if ($url) {
    // Редирект по ссылкам в текстах, обработанным функцией tags()
    if (isset($_POST['submit'])) {
        header('Location: ' . $url);
    } else {
        require 'system/head.php';
        echo '<div class="phdr"><b>' . _t('External Link', 'system') . '</b></div>' .
            '<div class="rmenu">' .
            '<form action="go.php?url=' . rawurlencode($url) . '" method="post">' .
            '<p><h3>' . _t('ATTENTION!', 'system') . '</h3>' .
            _t('You are going to leave our site and go to an external link',
                'system') . ':<br /><span class="red">' . htmlspecialchars($url) . '</span></p>' .
            '<p>' . _t('Administration of our site is not responsible for the content of external sites',
                'system') . '.<br />' .
            sprintf(_t('It is recommended not to specify your data, relating to %s (Login, Password), on third party sites',
                'system'), '<span class="green">' . $config->homeurl . '</span>') . '.</p>' .
            '<p><input type="submit" name="submit" value="' . _t('Go to Link', 'system') . '" /></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="' . $referer . '">' . _t('Back', 'system') . '</a></div>';
        require 'system/end.php';
    }
} elseif ($id) {
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
        header('Location: http://johncms.com/index.php?act=404');
    }
}
