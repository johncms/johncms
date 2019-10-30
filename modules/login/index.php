<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ConfigInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var ConfigInterface $config */
$config = $container->get(ConfigInterface::class);

/** @var UserInterface $user */
$user = $container->get(UserInterface::class);

/** @var Engine $view */
$view = $container->get(Engine::class);
$view->addFolder('login', __DIR__ . '/templates/');

$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config->homeurl;

if($user->isValid()){
    if (isset($_POST['submit'])) {
        setcookie('cuid', '', time() - 3600, '/');
        setcookie('cups', '', time() - 3600, '/');
        session_destroy();
        header('Location: ' . $config->homeurl);
    } else {
        echo $view->render('login::logout', ['referer' => $referer]);
    }

} else {
    require __DIR__ . '/includes/login.php';
}
