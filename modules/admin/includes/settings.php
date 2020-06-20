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

$config = di('config')['johncms'];
$nav_chain->add(__('System Settings'));

if (isset($_POST['submit'])) {
    // Сохраняем настройки системы
    $config['skindef'] = $request->getPost('skindef', 'default', FILTER_SANITIZE_STRING);
    $config['email'] = $request->getPost('madm', 'example@example.com', FILTER_SANITIZE_STRING);
    $config['timeshift'] = $request->getPost('timeshift', 0, FILTER_VALIDATE_INT);
    $config['copyright'] = $request->getPost('copyright', 'JohnCMS', FILTER_SANITIZE_STRING);

    $current_host = 'https://' . $request->getServer('HTTP_HOST', '', FILTER_SANITIZE_STRING);
    $config['homeurl'] = rtrim($request->getPost('homeurl', $current_host, FILTER_SANITIZE_STRING), '/');

    $config['flsz'] = $request->getPost('flsz', 0, FILTER_VALIDATE_INT);
    $config['gzip'] = $request->getPost('gz', 0, FILTER_VALIDATE_INT);
    $config['meta_title'] = $request->getPost('meta_title', 'johncms', FILTER_SANITIZE_STRING);
    $config['meta_key'] = $request->getPost('meta_key', 'johncms', FILTER_SANITIZE_STRING);
    $config['meta_desc'] = $request->getPost('meta_desc', 'johncms', FILTER_SANITIZE_STRING);
    $config['user_email_required'] = $request->getPost('user_email_required', 0, FILTER_VALIDATE_INT);
    $config['user_email_confirmation'] = $request->getPost('user_email_confirmation', 0, FILTER_VALIDATE_INT);

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

echo $view->render(
    'admin::settings',
    [
        'sysconf'      => $config,
        'confirmation' => $confirmation ?? false,
        'themelist'    => array_map('basename', glob(ROOT_PATH . 'themes/*', GLOB_ONLYDIR)),
    ]
);
