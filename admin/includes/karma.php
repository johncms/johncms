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

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

$config = $container->get('config')['johncms'];

// Проверяем права доступа
if ($systemUser->rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

if ($systemUser->rights == 9 && $do == 'clean') {
    if (isset($_GET['yes'])) {
        $db->query("TRUNCATE TABLE `karma_users`");
        $db->exec("UPDATE `users` SET `karma_plus` = 0, `karma_minus` = 0");
        echo '<div class="gmenu">' . _t('Karma is cleared') . '</div>';
    } else {
        echo '<div class="rmenu"><p>' . _t('You really want to clear the Karma?') . '<br>' .
            '<a href="index.php?act=karma&amp;do=clean&amp;yes">' . _t('Clear') . '</a> | ' .
            '<a href="index.php?act=karma">' . _t('Cancel') . '</a></p></div>';
    }
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Karma') . '</div>';
$settings = $config['karma'];

if (isset($_POST['submit'])) {
    $settings['karma_points'] = isset($_POST['karma_points']) ? abs(intval($_POST['karma_points'])) : 0;
    $settings['karma_time'] = isset($_POST['karma_time']) ? abs(intval($_POST['karma_time'])) : 0;
    $settings['forum'] = isset($_POST['forum']) ? abs(intval($_POST['forum'])) : 0;
    $settings['time'] = isset($_POST['time']) ? abs(intval($_POST['time'])) : 0;
    $settings['on'] = isset($_POST['on']) ? 1 : 0;
    $settings['adm'] = isset($_POST['adm']) ? 1 : 0;
    $settings['karma_time'] = $settings['time'] ? $settings['karma_time'] * 3600 : $settings['karma_time'] * 86400;

    $config['karma'] = $settings;
    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

$settings['karma_time'] = $settings['time'] ? $settings['karma_time'] / 3600 : $settings['karma_time'] / 86400;
echo '<form action="index.php?act=karma" method="post"><div class="menu">' .
    '<p><h3>' . _t('Voices per day') . '</h3>' .
    '<input type="text" name="karma_points" value="' . $settings['karma_points'] . '"/></p>' .
    '<p><h3>' . _t('Restriction for vote') . '</h3>' .
    '<input type="text" name="forum" value="' . $settings['forum'] . '" size="4"/>&#160;' . _t('Forum posts') . '<br>' .
    '<input type="text" name="karma_time" value="' . $settings['karma_time'] . '" size="4"/>&#160;' . _t('Stay on site') . '<br>' .
    '&#160;<input name="time" type="radio" value="1"' . ($settings['time'] ? ' checked="checked"' : '') . '/>&#160;' . _t('Hours') . '<br>' .
    '&#160;<input name="time" type="radio" value="0"' . (!$settings['time'] ? ' checked="checked"' : '') . '/>&#160;' . _t('Days') . '</p>' .
    '<p><h3>' . _t('General Settings') . '</h3>' .
    '<input type="checkbox" name="on"' . ($settings['on'] ? ' checked="checked"' : '') . '/> ' . _t('Switch module ON') . '<br>' .
    '<input type="checkbox" name="adm"' . ($settings['adm'] ? ' checked="checked"' : '') . '/> ' . _t('Forbid to vote for the administration') . '</p>' .
    '<p><input type="submit" value="' . _t('Save') . '" name="submit" /></p></div>' .
    '</form><div class="phdr">' . ($systemUser->rights == 9 ? '<a href="index.php?act=karma&amp;do=clean">' . _t('Clear Karma') . '</a>' : '<br>') . '</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
