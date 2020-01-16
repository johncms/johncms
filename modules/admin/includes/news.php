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

$data = [];

$title = __('News on the mainpage');
$nav_chain->add($title);

// Получаем сохраненные настройки
$settings = $config['news'];

// Настройки Новостей
if (isset($_POST['submit'])) {
    // Принимаем настройки из формы
    $settings['view'] = isset($_POST['view']) && $_POST['view'] >= 0 && $_POST['view'] < 4 ? (int) ($_POST['view']) : 1;
    $settings['size'] = isset($_POST['size']) && $_POST['size'] > 49 && $_POST['size'] < 501 ? (int) ($_POST['size']) : 200;
    $settings['quantity'] = isset($_POST['quantity']) && $_POST['quantity'] > 0 && $_POST['quantity'] < 16 ? (int) ($_POST['quantity']) : 3;
    $settings['days'] = isset($_POST['days']) && $_POST['days'] > 0 && $_POST['days'] < 31 ? (int) ($_POST['days']) : 7;
    $settings['breaks'] = isset($_POST['breaks']);
    $settings['smileys'] = isset($_POST['smileys']);
    $settings['tags'] = isset($_POST['tags']);
    $settings['kom'] = isset($_POST['kom']);

    $config['news'] = $settings;
    $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";

    if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => __('ERROR: Can not write system.local.php'),
                'admin'         => true,
                'menu_item'     => 'news',
                'parent_menu'   => 'module_menu',
                'back_url'      => '/admin/news/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    $data['success_message'] = __('Settings are saved successfully');

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

$data['form_action'] = '/admin/news/';
$data['settings'] = $settings;

echo $view->render(
    'admin::news',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
