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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Подробная информация, контактные данные
$textl = htmlspecialchars($user['name']) . ': ' . _t('Information');
require('../system/head.php');
echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Information') . '</div>';

if ($user['id'] == $systemUser->id || ($systemUser->rights >= 7 && $systemUser->rights > $user['rights'])) {
    echo '<div class="topmenu"><a href="?act=edit&amp;user=' . $user['id'] . '">' . _t('Edit') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($user) . '</p></div>' .
    '<div class="list2"><p>' .
    '<h3><img src="../images/contacts.png" width="16" height="16" class="left" />&#160;' . _t('Personal info') . '</h3>' .
    '<ul>';

if (file_exists('../files/users/photo/' . $user['id'] . '_small.jpg')) {
    echo '<a href="../files/users/photo/' . $user['id'] . '.jpg"><img src="../files/users/photo/' . $user['id'] . '_small.jpg" alt="' . $user['name'] . '" border="0" /></a>';
}

echo '<li><span class="gray">' . _t('Name') . ':</span> ' . (empty($user['imname']) ? '' : $user['imname']) . '</li>' .
    '<li><span class="gray">' . _t('Birthday') . ':</span> ' . (empty($user['dayb']) ? '' : sprintf("%02d", $user['dayb']) . '.' . sprintf("%02d", $user['monthb']) . '.' . $user['yearofbirth']) . '</li>' .
    '<li><span class="gray">' . _t('City, Country') . ':</span> ' . (empty($user['live']) ? '' : $user['live']) . '</li>' .
    '<li><span class="gray">' . _t('About myself') . ':</span> ' . (empty($user['about']) ? '' : '<br />' . $tools->smilies($tools->checkout($user['about'], 1, 1))) . '</li>' .
    '</ul></p><p>' .
    '<h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . _t('Contacts') . '</h3><ul>' .
    '<li><span class="gray">' . _t('Phone number') . ':</span> ' . (empty($user['mibile']) ? '' : $user['mibile']) . '</li>' .
    '<li><span class="gray">E-mail:</span> ';

if (!empty($user['mail']) && $user['mailvis'] || $systemUser->rights >= 7 || $user['id'] == $systemUser->id) {
    echo $user['mail'] . ($user['mailvis'] ? '' : '<span class="gray"> [' . _t('hidden') . ']</span>');
}

echo '</li>' .
    '<li><span class="gray">ICQ:</span> ' . (empty($user['icq']) ? '' : $user['icq']) . '</li>' .
    '<li><span class="gray">Skype:</span> ' . (empty($user['skype']) ? '' : $user['skype']) . '</li>' .
    '<li><span class="gray">Jabber:</span> ' . (empty($user['jabber']) ? '' : $user['jabber']) . '</li>' .
    '<li><span class="gray">' . _t('Site') . ':</span> ' . (empty($user['www']) ? '' : $tools->checkout($user['www'], 0, 1)) . '</li>' .
    '</ul></p></div>' .
    '<div class="phdr"><a href="?user=' . $user['id'] . '">' . _t('Back') . '</a></div>';
