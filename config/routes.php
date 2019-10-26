<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use FastRoute\RouteCollector;
use Johncms\Api\UserInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var UserInterface $systemUser */
$systemUser = $container->get(UserInterface::class);

/** @var RouteCollector $map */
$map = $container->get(RouteCollector::class);

$map->get('/', 'modules/homepage/index.php');                                          // Главная страница
$map->get('/rss[/]', 'modules/rss/index.php');                                         // RSS
$map->addRoute(['GET', 'POST'], '/album[/]', 'modules/album/index.php');               // Фотоальбомы
$map->addRoute(['GET', 'POST'], '/downloads[/]', 'modules/downloads/index.php');       // Загрузки
$map->addRoute(['GET', 'POST'], '/forum[/]', 'modules/forum/index.php');               // Форум
$map->addRoute(['GET', 'POST'], '/guestbook[/]', 'modules/guestbook/index.php');       // Гостевая
$map->addRoute(['GET', 'POST'], '/help[/]', 'modules/help/index.php');                 // Справка
$map->addRoute(['GET', 'POST'], '/library[/]', 'modules/library/index.php');           // Библиотека
$map->addRoute(['GET', 'POST'], '/login[/]', 'modules/login/index.php');               // Вход / выход с сайта
$map->addRoute(['GET', 'POST'], '/mail[/]', 'modules/mail/index.php');                 // Почта
$map->addRoute(['GET', 'POST'], '/news[/]', 'modules/news/index.php');                 // Новости
$map->addRoute(['GET', 'POST'], '/profile[/]', 'modules/profile/index.php');           // Пользовательские профили
$map->addRoute(['GET', 'POST'], '/registration[/]', 'modules/registration/index.php'); // Регистрация
$map->addRoute(['GET', 'POST'], '/users[/]', 'modules/users/index.php');               // Пользователи (актив сайта)
//$map->addRoute(['GET', 'POST'], '/language[/]', 'modules/language/index.php');       // Переключатель языков
//$map->addRoute(['GET', 'POST'], '/redirect/', 'modules/redirect/index.php');         // Регистрация

if ($systemUser->isValid() && $systemUser->rights >= 6) {
    $map->addRoute(['GET', 'POST'], '/admin[/]', 'modules/admin/index.php');  // Админ панель
}

// Пример маршрута, где в URL присутствует index.php
// $map->addRoute(['GET', 'POST'], '/users[/[index.php]]', 'modules/users/index.php');