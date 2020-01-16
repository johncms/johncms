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
 */

$config = di('config')['johncms'];
$set_af = $config['antiflood'];

$title = __('Antiflood Settings');
$nav_chain->add($title);
$data = [];

if (isset($_POST['submit']) || isset($_POST['save'])) {
    // Принимаем данные из формы
    $set_af['mode'] = isset($_POST['mode']) && $_POST['mode'] > 0 && $_POST['mode'] < 5 ? (int) ($_POST['mode']) : 1;
    $set_af['day'] = isset($_POST['day']) ? (int) ($_POST['day']) : 10;
    $set_af['night'] = isset($_POST['night']) ? (int) ($_POST['night']) : 30;
    $set_af['dayfrom'] = isset($_POST['dayfrom']) ? (int) ($_POST['dayfrom']) : 10;
    $set_af['dayto'] = isset($_POST['dayto']) ? (int) ($_POST['dayto']) : 22;

    // Проверяем правильность ввода данных
    if ($set_af['day'] < 4) {
        $set_af['day'] = 4;
    }

    if ($set_af['day'] > 300) {
        $set_af['day'] = 300;
    }

    if ($set_af['night'] < 4) {
        $set_af['night'] = 4;
    }

    if ($set_af['night'] > 300) {
        $set_af['night'] = 300;
    }

    if ($set_af['dayfrom'] < 6) {
        $set_af['dayfrom'] = 6;
    }

    if ($set_af['dayfrom'] > 12) {
        $set_af['dayfrom'] = 12;
    }

    if ($set_af['dayto'] < 17) {
        $set_af['dayto'] = 17;
    }

    if ($set_af['dayto'] > 23) {
        $set_af['dayto'] = 23;
    }

    $config['antiflood'] = $set_af;
    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('ERROR: Can not write system.local.php'),
            ]
        );
        exit;
    }

    $data['success_message'] = __('Settings are saved successfully');
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

$data['form_action'] = '/admin/antiflood/';
$data['set_af'] = $set_af;

echo $view->render(
    'admin::antiflood',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
