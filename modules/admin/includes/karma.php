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

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

$config = di('config')['johncms'];
$data = [];

$title = __('Karma');
$nav_chain->add($title);

// Проверяем права доступа
if ($user->rights < 9) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access denied'),
        ]
    );
    exit;
}

if ($user->rights === 9 && $do === 'clean') {
    if (isset($_GET['yes'])) {
        $db->query('TRUNCATE TABLE `karma_users`');
        $db->exec('UPDATE `users` SET `karma_plus` = 0, `karma_minus` = 0');
        $data['success_message'][] = __('Karma is cleared');
    } else {
        $data['message'] = __('You really want to clear the Karma?');
        $data['confirm_url'] = '?do=clean&amp;yes';
        $data['back_url'] = '/admin/karma/';
        echo $view->render(
            'admin::karma_clean_confirm',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
        exit;
    }
}

$settings = $config['karma'];

if (isset($_POST['submit'])) {
    $settings['karma_points'] = isset($_POST['karma_points']) ? abs((int) ($_POST['karma_points'])) : 0;
    $settings['forum'] = isset($_POST['forum']) ? abs((int) ($_POST['forum'])) : 0;
    $settings['on'] = isset($_POST['on']) ? 1 : 0;
    $settings['adm'] = isset($_POST['adm']) ? 1 : 0;

    $config['karma'] = $settings;
    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    $data['success_message'][] = __('Settings are saved successfully');
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

$data['form_action'] = '/admin/karma/';
$data['settings'] = $settings;

echo $view->render(
    'admin::karma',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
