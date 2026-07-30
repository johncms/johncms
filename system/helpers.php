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
use Johncms\Config\ConfigRepository;
use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\PageNotFoundException;

/**
 * @param string $service
 * @return mixed
 */
function di(string $service): mixed
{
    // For backward compatibility return config()
    if ($service === 'config') {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? null;

        $location = $caller && isset($caller['file'], $caller['line'])
            ? sprintf('%s:%d', $caller['file'], $caller['line'])
            : 'unknown location';

        @trigger_error(
            sprintf(
                'Calling di("config") is deprecated. Use config() helper instead. Called at %s.',
                $location
            ),
            E_USER_DEPRECATED
        );
        return config();
    }

    return \Johncms\Container\PSRContainerFactory::getContainer()->get($service);
}

function pathToUrl(string $path): string
{
    return di(\Johncms\Http\PublicUrlResolver::class)->fromPath($path);
}

/**
 * Answer the request with the 404 page.
 *
 * Throws instead of rendering and exiting: the HTTP layer catches the exception and builds
 * the response, so the page can be rendered once, in one place.
 *
 * @throws PageNotFoundException
 * @throws HttpRedirectException if the current URI is listed in config/redirects.php
 */
function pageNotFound(
    string $template = 'system::error/404',
    string $title = '',
    string $message = ''
): never {
    checkRedirect();

    throw (new PageNotFoundException($message))
        ->setTemplate($template)
        ->setTitle($title);
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

/**
 * Redirect the request if its URI is listed in config/redirects.php.
 *
 * @throws HttpRedirectException
 */
function checkRedirect(): void
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $redirects = require CONFIG_PATH . 'redirects.php';
    if (! array_key_exists($requestUri, $redirects)) {
        return;
    }

    $target = $redirects[$requestUri];
    if (! is_string($target) || $target === '') {
        throw new RuntimeException(
            sprintf('The redirect target for "%s" in config/redirects.php must be a non-empty string.', $requestUri)
        );
    }

    redirect($target, 301);
}

/**
 * Answer the request with an HTTP redirect.
 *
 * Throws instead of sending a Location header and exiting: the HTTP layer catches the exception
 * and builds the response, so nothing is written to the output from the middle of an action.
 *
 * @throws HttpRedirectException
 */
function redirect(string $url, int $status = 302): never
{
    throw new HttpRedirectException($url, $status);
}

/**
 * Get config value
 *
 * @param string|null $key    - config key with dot notation
 * @param mixed|null $default - default value
 * @return mixed
 */
function config(?string $key = null, mixed $default = null): mixed
{
    if ($key === null) {
        return ConfigRepository::all();
    }

    return ConfigRepository::get($key, $default);
}
