<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

$config = di('config')['johncms'];
$errorMsg = false;
$title = __('Permissions');
$nav_chain->add($title);

if (isset($_POST['submit'])) {
    $config['mod_reg'] = $request->getPost('reg', 0, FILTER_VALIDATE_INT);
    $config['mod_forum'] = $request->getPost('forum', 0, FILTER_VALIDATE_INT);
    $config['mod_guest'] = $request->getPost('guest', 0, FILTER_VALIDATE_INT);
    $config['mod_lib'] = $request->getPost('lib', 0, FILTER_VALIDATE_INT);
    $config['mod_lib_comm'] = isset($_POST['libcomm']);
    $config['mod_down'] = $request->getPost('down', 0, FILTER_VALIDATE_INT);
    $config['mod_down_comm'] = isset($_POST['downcomm']);
    $config['active'] = $request->getPost('active', 0, FILTER_VALIDATE_INT);

    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        $errorMsg = true;
    } else {
        $confirmation = true;
    }

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo $view->render(
    'admin::access',
    [
        'title'        => $title,
        'page_title'   => $title,
        'conf'         => $config,
        'errorMsg'     => $errorMsg,
        'confirmation' => $confirmation ?? false,
    ]
);
