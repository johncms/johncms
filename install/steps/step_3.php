<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Install\Database;
use Johncms\System\Http\Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/** @var Request $request */
$request = di(Request::class);

$view->addData(
    [
        'title'      => 'База данных',
        'page_title' => 'База данных',
    ]
);

$fields = [
    'db_host'     => $request->getPost('db_host', 'localhost', FILTER_SANITIZE_STRING),
    'db_port'     => $request->getPost('db_port', 3306, FILTER_VALIDATE_INT),
    'db_name'     => $request->getPost('db_name', 'johncms', FILTER_SANITIZE_STRING),
    'db_user'     => $request->getPost('db_user', '', FILTER_SANITIZE_STRING),
    'db_password' => $request->getPost('db_password', '', FILTER_SANITIZE_SPECIAL_CHARS),
];

$errors = [];

if ($request->getMethod() === 'POST') {
    $capsule = new Capsule();
    $capsule->addConnection(
        [
            'driver'    => 'mysql',
            'host'      => $fields['db_host'],
            'port'      => $fields['db_port'],
            'database'  => $fields['db_name'],
            'username'  => $fields['db_user'],
            'password'  => $fields['db_password'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'timezone'  => '+00:00',
        ]
    );
    $capsule->setAsGlobal();
    $connection = $capsule->getConnection();

    try {
        $connection->getPdo();

        // Создаем системный файл database.local.php
        $db_settings = [
            'pdo' => [
                'db_host' => $fields['db_host'],
                'db_port' => $fields['db_port'],
                'db_name' => $fields['db_name'],
                'db_user' => $fields['db_user'],
                'db_pass' => $fields['db_password'],
            ],
        ];
        $db_file = "<?php\n\n" . 'return ' . var_export($db_settings, true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/database.local.php', $db_file)) {
            Database::createTables();
            header('Location: /install/?step=4');
        } else {
            $errors['unknown'][] = 'ERROR: Can not write database.local.php';
        }
    } catch (Exception $exception) {
        $db_error = $exception->getMessage();
        $error_code = $exception->getCode();
        if ($error_code === 2002) {
            $errors['db_host'][] = __('Invalid DataBase host name');
        } elseif ($error_code === 1045) {
            $errors['db_user'][] = __('Invalid DataBase user');
        } elseif ($error_code === 1049) {
            $errors['db_name'][] = __('Database does not exist');
        } else {
            $errors['unknown'][] = $db_error;
        }
    }
}


$data = [
    'errors'             => $errors,
    'fields'             => $fields,
    'next_step_disabled' => false,
];

echo $view->render('install::step_3', ['data' => $data]);
