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

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var NavChain $nav_chain
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

$config = di('config')['johncms'];

$view->addData(['title' => __('Languages'), 'page_title' => __('Languages')]);

if (isset($_POST['lng']) || isset($_POST['update'])) {
    if (isset($_POST['lng'])) {
        $select = trim($_POST['lng']);

        if (isset($config['lng_list'][$select])) {
            $config['lng'] = $select;
        }
    }

    if (isset($_POST['update'])) {
        // Обновляем список имеющихся языков
        $lng_list = [];

        foreach (glob(ROOT_PATH . 'system/locale/*.ini') as $val) {
            $iso = pathinfo($val, PATHINFO_FILENAME);
            $lang_data = parse_ini_file($val);
            $lng_list[$iso] = [
                'name'    => ! empty($lang_data['name']) ? $lang_data['name'] : $iso,
                'version' => ! empty($lang_data['version']) ? (float) $lang_data['version'] : 1,
            ];
        }

        $config['lng_list'] = $lng_list;
        $confirmation = true;
    }

    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo $view->render(
    'admin::languages/index',
    [
        'config'       => $config,
        'confirmation' => $confirmation ?? false,
    ]
);
