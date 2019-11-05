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

// Подробная информация, контактные данные
$textl = htmlspecialchars($foundUser['name']) . ': ' . _t('Information');
echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Information') . '</div>';

if ($foundUser['id'] == $user->id || ($user->rights >= 7 && $user->rights > $foundUser['rights'])) {
    echo '<div class="topmenu"><a href="?act=edit&amp;user=' . $foundUser['id'] . '">' . _t('Edit') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($foundUser) . '</p></div>' .
    '<div class="list2"><p>' .
    '<h3><img src="../images/contacts.png" width="16" height="16" class="left" />&#160;' . _t('Personal info') . '</h3>' .
    '<ul>';

if (file_exists(UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '_small.jpg')) {
    echo '<a href="../upload/users/photo/' . $foundUser['id'] . '.jpg"><img src="../upload/users/photo/' . $foundUser['id'] . '_small.jpg" alt="' . $foundUser['name'] . '" border="0" /></a>';
}

echo '<li><span class="gray">' . _t('Name') . ':</span> ' . (empty($foundUser['imname']) ? '' : $foundUser['imname']) . '</li>' .
    '<li><span class="gray">' . _t('Birthday') . ':</span> ' . (empty($foundUser['dayb']) ? '' : sprintf('%02d', $foundUser['dayb']) . '.' . sprintf('%02d', $foundUser['monthb']) . '.' . $foundUser['yearofbirth']) . '</li>' .
    '<li><span class="gray">' . _t('City, Country') . ':</span> ' . (empty($foundUser['live']) ? '' : $foundUser['live']) . '</li>' .
    '<li><span class="gray">' . _t('About myself') . ':</span> ' . (empty($foundUser['about']) ? '' : '<br />' . $tools->smilies($tools->checkout($foundUser['about'], 1, 1))) . '</li>' .
    '</ul></p><p>' .
    '<h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . _t('Contacts') . '</h3><ul>' .
    '<li><span class="gray">' . _t('Phone number') . ':</span> ' . (empty($foundUser['mibile']) ? '' : $foundUser['mibile']) . '</li>' .
    '<li><span class="gray">E-mail:</span> ';

if ((! empty($foundUser['mail']) && $foundUser['mailvis']) || $user->rights >= 7 || $foundUser['id'] == $user->id) {
    echo $foundUser['mail'] . ($foundUser['mailvis'] ? '' : '<span class="gray"> [' . _t('hidden') . ']</span>');
}

echo '</li>' .
    '<li><span class="gray">ICQ:</span> ' . (empty($foundUser['icq']) ? '' : $foundUser['icq']) . '</li>' .
    '<li><span class="gray">Skype:</span> ' . (empty($foundUser['skype']) ? '' : $foundUser['skype']) . '</li>' .
    '<li><span class="gray">Jabber:</span> ' . (empty($foundUser['jabber']) ? '' : $foundUser['jabber']) . '</li>' .
    '<li><span class="gray">' . _t('Site') . ':</span> ' . (empty($foundUser['www']) ? '' : $tools->checkout($foundUser['www'], 0, 1)) . '</li>' .
    '</ul></p></div>' .
    '<div class="phdr"><a href="?user=' . $foundUser['id'] . '">' . _t('Back') . '</a></div>';
