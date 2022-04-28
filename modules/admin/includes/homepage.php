<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\Http\Request;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var NavChain $nav_chain
 * @var Johncms\System\Users\User $user
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

/** @var Request $request */
$request = di(Request::class);

$homepage_config = di('config')['homepage'];
$nav_chain->add('Главная страница');

if (isset($_POST['submit'])) {
    // Сохраняем настройки системы
    $homepage_config['last_themes'] = $request->getPost('last_themes', 0, FILTER_VALIDATE_INT);
    $homepage_config['last_files'] = $request->getPost('last_files', 0, FILTER_VALIDATE_INT);
    $homepage_config['last_lib'] = $request->getPost('last_lib', 0, FILTER_VALIDATE_INT);
    $homepage_config['show_demo'] = $request->getPost('show_demo', 0, FILTER_VALIDATE_INT);

    $configFile = "<?php\n\n" . 'return ' . var_export(['homepage' => $homepage_config], true) . ";\n";

    if (!file_put_contents(CONFIG_PATH . 'autoload/homepage.local.php', $configFile)) {
        echo 'ERROR: Can not write homepage.local.php</body></html>';
        exit;
    }

    $confirmation = true;

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    header('location: /admin/homepage/');
}


echo $view->render(
    'admin::homepage',
    [
        'config'      => $homepage_config,
        'confirmation' => $confirmation ?? false,
    ]
);
