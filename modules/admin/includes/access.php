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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

$config = $container->get('config')['johncms'];
$confirmation = false;
$errorMsg = false;

if (isset($_POST['submit'])) {
    $config['mod_reg'] = (int) ($_POST['reg']) ?? 0;
    $config['mod_forum'] = (int) ($_POST['forum']) ?? 0;
    $config['mod_guest'] = (int) ($_POST['guest']) ?? 0;
    $config['mod_lib'] = (int) ($_POST['lib']) ?? 0;
    $config['mod_lib_comm'] = isset($_POST['libcomm']);
    $config['mod_down'] = (int) ($_POST['down']) ?? 0;
    $config['mod_down_comm'] = isset($_POST['downcomm']);
    $config['active'] = (int) ($_POST['active']) ?? 0;
    $config['site_access'] = (int) ($_POST['access']) ?? 0;

    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        $errorMsg = true;
    } else {
        $confirmation = _t('Settings are saved successfully');
        $confirmation = true;
    }

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo $view->render('admin::access', [
    'conf'         => $config,
    'errorMsg'     => $errorMsg,
    'confirmation' => $confirmation,
]);
