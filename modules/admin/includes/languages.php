<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Utility\NavChain;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights < 9) {
    exit(_t('Access denied'));
}

$config = di('config')['johncms'];

/** @var NavChain $navChain */
$navChain = di(NavChain::class);
$navChain->add(_t('Admin Panel'), '../');
$navChain->add(_t('Default language'));

// Выводим список доступных языков
//echo '<div class="phdr"><a href="./"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Default language') . '</div>';

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

        foreach (glob(ROOT_PATH . 'system/locale/*/lng.ini') as $val) {
            $iso = basename(dirname($val));
            $desc = parse_ini_file($val);
            $lng_list[$iso] = isset($desc['name']) && ! empty($desc['name']) ? $desc['name'] : $iso;
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
    'admin::languages',
    [
        'config'       => $config,
        'confirmation' => $confirmation ?? false,
    ]
);
