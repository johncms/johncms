<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var Psr\Container\ContainerInterface $container
 * @var Johncms\Api\UserInterface        $user
 */

if ($user->rights < 9) {
    exit(_t('Access denied'));
}

$config = $container->get('config')['johncms'];

if (isset($_POST['submit'])) {
    // Сохраняем настройки системы
    $config['skindef'] = isset($_POST['skindef']) ? trim($_POST['skindef']) : 'default';
    $config['email'] = isset($_POST['madm']) ? trim($_POST['madm']) : '@';
    $config['timeshift'] = isset($_POST['timeshift']) ? (int) ($_POST['timeshift']) : 0;
    $config['copyright'] = isset($_POST['copyright']) ? trim($_POST['copyright']) : 'JohnCMS';
    $config['homeurl'] = isset($_POST['homeurl']) ? preg_replace('#/$#', '', trim($_POST['homeurl'])) : '/';
    $config['flsz'] = isset($_POST['flsz']) ? (int) ($_POST['flsz']) : 0;
    $config['gzip'] = isset($_POST['gz']);
    $config['meta_key'] = isset($_POST['meta_key']) ? trim($_POST['meta_key']) : 'johncms';
    $config['meta_desc'] = isset($_POST['meta_desc']) ? trim($_POST['meta_desc']) : 'johncms';

    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    $confirmation = true;

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo $view->render('admin::settings', [
    'sysconf'      => $config,
    'confirmation' => $confirmation ?? false,
    'themelist'    => array_map('basename', glob(ROOT_PATH . 'themes/*', GLOB_ONLYDIR)),
]);
