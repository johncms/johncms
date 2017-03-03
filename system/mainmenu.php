<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Johncms\Counters $counters */
$counters = $container->get('counters');

$mp = new Johncms\NewsWidget();

// Блок информации
echo '<div class="phdr"><b>' . _t('Information', 'system') . '</b></div>';
echo $mp->news;
echo '<div class="menu"><a href="news/">' . _t('News archive', 'system') . '</a> (' . $mp->newscount . ')</div>' .
    '<div class="menu"><a href="help/">' . _t('Information, FAQ', 'system') . '</a></div>';

////////////////////////////////////////////////////////////
// Блок общения                                           //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><b>' . _t('Communication', 'system') . '</b></div>';

// Ссылка на гостевую
if ($config->mod_guest || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="guestbook/index.php">' . _t('Guestbook', 'system') . '</a> (' . $counters->guestbook() . ')</div>';
}

// Ссылка на Форум
if ($config->mod_forum || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="forum/">' . _t('Forum', 'system') . '</a> (' . $counters->forum() . ')</div>';
}

////////////////////////////////////////////////////////////
// Блок полезного                                         //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><b>' . _t('Useful', 'system') . '</b></div>';

// Ссылка на загрузки
if ($config->mod_down || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="downloads/">' . _t('Downloads', 'system') . '</a> (' . $counters->downloads() . ')</div>';
}

// Ссылка на библиотеку
if ($config->mod_lib || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="library/">' . _t('Library', 'system') . '</a> (' . $counters->library() . ')</div>';
}

////////////////////////////////////////////////////////////
// Блок Сообщества                                        //
////////////////////////////////////////////////////////////
if ($systemUser->isValid() || $config->active) {
    echo '<div class="phdr"><b>' . _t('Community', 'system') . '</b></div>' .
        '<div class="menu"><a href="users/index.php">' . _t('Users', 'system') . '</a> (' . $counters->users() . ')</div>' .
        '<div class="menu"><a href="album/index.php">' . _t('Photo Albums', 'system') . '</a> (' . $counters->album() . ')</div>';
}

echo '<div class="phdr"><a href="http://gazenwagen.com">Gazenwagen</a></div>';
