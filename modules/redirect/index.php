<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Mobicms\Render\Engine;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Engine $view */

$view = di(Engine::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('redirect', __DIR__ . '/templates/');

$url = isset($_REQUEST['url']) ? strip_tags(rawurldecode(trim($_REQUEST['url']))) : false;

if ($url) {
    // Редирект по ссылкам в текстах, обработанным функцией tags()
    if (isset($_POST['submit'])) {
        header('Location: ' . $url);
    } else {
        echo $view->render('redirect::index', [
            'redirect_url' => rawurlencode($url),
            'referer'      => (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '/'),
            'url'          => $url,
        ]);
    }
}
