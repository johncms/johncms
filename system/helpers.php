<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Container\Factory;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;

function di(string $service)
{
    return Factory::getContainer()->get($service);
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
 */
function pageNotFound(
    string $template = 'system::error/404',
    string $title = 'ERROR: 404 Not Found',
    string $message = ''
): void {
    $engine = di(Render::class);

    if (! headers_sent()) {
        header('HTTP/1.0 404 Not Found');
    }

    exit(
    $engine->render(
        $template,
        [
            'title'   => $title,
            'message' => ! empty($message)
                ? $message
                : __('You are looking for something that doesn\'t exist or may have moved'),
        ]
    )
    );
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

/**
 * Обёртка над функцией print_r
 *
 * @param mixed $var
 * @param bool $to_file
 */
if (! function_exists('d')) {
    function d($var = false, $to_file = false)
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
