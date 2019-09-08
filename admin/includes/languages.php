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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

$config = $container->get('config')['johncms'];

// Проверяем права доступа
if ($systemUser->rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}

// Выводим список доступных языков
echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Default language') . '</div>';

if (isset($_POST['lng']) || isset($_GET['refresh'])) {
    if (isset($_POST['lng'])) {
        $select = trim($_POST['lng']);

        if(isset($config['lng_list'][$select])){
            $config['lng'] = $select;
        }
    } elseif (isset($_GET['refresh'])) {
        // Обновляем список имеющихся языков
        $lng_list = [];

        foreach (glob(ROOT_PATH . 'system/locale/*/lng.ini') as $val) {
            $iso = basename(dirname($val));
            $desc = parse_ini_file($val);
            $lng_list[$iso] = isset($desc['name']) && !empty($desc['name']) ? $desc['name'] : $iso;
        }

        $config['lng_list'] = $lng_list;
        echo '<div class="gmenu"><p>' . _t('Descriptions have been updated successfully') . '</p></div>';
    }

    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo '<div class="menu">'
    . '<form action="?act=languages" method="post">'
    . '<p><h3>' . _t('Select language') . '</h3>';

foreach ($config['lng_list'] as $key => $val) {
    echo '<div><input type="radio" value="' . $key . '" name="lng" ' . ($key == $config['lng'] ? 'checked="checked"' : '') . '/>&#160;' .
        $tools->getFlag($key) .
        $val .
        ($key == $config->lng ? ' <small class="red">[' . _t('Default', 'system') . ']</small>' : '') .
        '</div>';
}

echo '</p><p>'
    . '<input type="submit" name="submit" value="' . _t('Apply') . '" />'
    . '</p></form></div>'
    . '<div class="phdr">' . _t('Total') . ': <b>' . count($config['lng_list']) . '</b></div><p>'
    . '<a href="index.php?act=languages&amp;refresh">' . _t('Update List') . '</a><br>'
    . '<a href="index.php">' . _t('Admin Panel') . '</a></p>';
