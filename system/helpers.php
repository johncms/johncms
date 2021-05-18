<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Aura\Autoload\Loader;
use Illuminate\Container\Container;
use Johncms\System\Container\Factory;
use Johncms\System\View\Render;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

/**
 * @param string $service
 * @return mixed
 */
function di(string $service)
{
    try {
        return Factory::getContainer()->get($service);
    } catch (ServiceNotFoundException $exception) {
        return Container::getInstance()->get($service);
    }
}

function pathToUrl(string $path): string
{
    $diff = array_diff(
        explode(DIRECTORY_SEPARATOR, realpath($path)),
        explode(DIRECTORY_SEPARATOR, realpath(ROOT_PATH))
    );
    return '/' . implode('/', $diff);
}

/**
 * Отображение ошибки 404
 *
 * @param string $template
 * @param string $title
 * @param string $message
 * @return never-return
 */
function pageNotFound(
    string $template = 'system::error/404',
    string $title = 'ERROR: 404 Not Found',
    string $message = ''
): void {
    checkRedirect();

    $engine = di(Render::class);

    if (! headers_sent()) {
        header('HTTP/1.0 404 Not Found');
    }

    echo $engine->render(
        $template,
        [
            'title'   => $title,
            'message' => ! empty($message)
                ? $message
                : __('You are looking for something that doesn\'t exist or may have moved'),
        ]
    );
    exit;
}

/**
 * array_key_last для php версий ниже 7.3
 *
 * @param array $array
 */
if (! function_exists('array_key_last')) {
    function array_key_last($array)
    {
        if (! is_array($array) || empty($array)) {
            return null;
        }

        return array_keys($array)[count($array) - 1];
    }
}

if (! function_exists('d')) {
    /**
     * Обёртка над функцией print_r
     *
     * @param mixed $var
     * @param bool $to_file
     */
    function d($var = false, $to_file = false): void
    {
        if ($to_file) {
            $file = fopen(DATA_PATH . 'debug.log', 'a');
            if ($file) {
                fwrite($file, print_r($var, true) . "\r\n");
                fclose($file);
            }
        }
        if (! $to_file || $to_file == 2) {
            echo '<pre>' . print_r($var, true) . '</pre>';
        }
    }
}

/**
 * Convert bytes to KB/MB/GB/TB
 *
 * @param $bytes
 * @return string
 */
function format_size(int $bytes): string
{
    if ($bytes < 1000 * 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    if ($bytes < 1000 * 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes < 1000 * 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1099511627776, 2) . ' TB';
}

/**
 * Registering an autoloader for the module
 *
 * @param $module_name
 * @param string $dir
 * @deprecated Use the module's root directory as the folder for the namespace.
 */
function module_lib_loader($module_name, $dir = 'lib')
{
    $loader = new Loader();
    $loader->register();
    $loader->addPrefix(ucfirst($module_name), ROOT_PATH . 'modules/' . $module_name . '/' . $dir);
}

function checkRedirect()
{
    $redirects = require CONFIG_PATH . 'redirects.php';
    if (array_key_exists($_SERVER['REQUEST_URI'], $redirects)) {
        http_response_code(301);
        header('Location: ' . $redirects[$_SERVER['REQUEST_URI']]);
        exit;
    }
}

/**
 * @param string $url
 * @return never-return
 */
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}
