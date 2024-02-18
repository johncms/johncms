<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use Johncms\Container\ContainerFactory;
use Johncms\Http\ResponseFactory;
use Johncms\Router\Router;
use Johncms\View\Render;
use Psr\Http\Message\ResponseInterface;

/**
 * @param string $service
 * @return mixed
 */
function di(string $service): mixed
{
    return ContainerFactory::getContainer()->get($service);
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
 * @throws Throwable
 * @deprecated use the status_page() function
 */
#[NoReturn]
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
 * Status page
 *
 * @param int $status_code
 * @param string|null $template
 * @param string|null $title
 * @param string|null $message
 * @return ResponseInterface
 * @throws Throwable
 */
function status_page(int $status_code, ?string $template = null, ?string $title = null, ?string $message = null): ResponseInterface
{
    $default_params = [
        403 => [
            'template' => 'system::error/403',
            'title'    => '403 | Access Denied',
            'message'  => __('Unfortunately, you do not have access to the requested page.'),
        ],
        404 => [
            'template' => 'system::error/404',
            'title'    => '404 | Page Not Found',
            'message'  => __('You are looking for something that doesn\'t exist or may have moved'),
        ],
        419 => [
            'template' => 'system::error/419',
            'title'    => '419 | Page Expired',
            'message'  => __('The page is outdated or an attempt to fake the request was prevented.'),
        ],
    ];

    $engine = di(Render::class);
    $response = di(ResponseFactory::class)->createResponse($status_code);
    $response->getBody()->write(
        $engine->render(
            $template ?? $default_params[$status_code]['template'],
            [
                'title'   => $title ?? $default_params[$status_code]['title'],
                'message' => $message ?? $default_params[$status_code]['message'],
            ]
        )
    );
    return $response;
}

if (! function_exists('d')) {
    /**
     * Обёртка над функцией print_r
     *
     * @param mixed $var
     * @param int $to_file
     */
    function d(mixed $var = false, int $to_file = 0): void
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
 * @param int $bytes
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

function checkRedirect()
{
    /** @var array<string, string> $redirects */
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
#[NoReturn]
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Build url by route name
 *
 * @param string $routeName
 * @param array $params
 * @param array $queryParams
 * @return string
 */
function route(string $routeName, array $params = [], array $queryParams = [], bool $withHost = false): string
{
    $router = di(Router::class);
    $url = $router->getUrlGenerator()->generate($routeName, $params);
    $queryString = http_build_query($queryParams);
    $url = str_replace('//', '/', $url) . (! empty($queryString) ? '?' . $queryString : '');

    return ($withHost ? rtrim(config('johncms.home_url', ''), '/') : '') . $url;
}

/**
 * Get a value from config
 *
 * @param string|int|null $key
 * @param mixed $default
 * @return mixed
 */
function config(mixed $key = null, mixed $default = null): mixed
{
    /** @var array $config */
    $config = di('config');
    return \Illuminate\Support\Arr::get($config, $key, $default);
}

/**
 * Format the date to the desired format
 *
 * @param $date                - source date
 * @param bool $withoutTime    - show only date
 * @param bool $withoutSeconds - show date and time without seconds
 * @return string
 */
function format_date(mixed $date, bool $withoutTime = false, bool $withoutSeconds = false): string
{
    if (empty($date)) {
        return '';
    }
    try {
        if (is_integer($date)) {
            $date_object = Carbon::createFromTimestamp($date);
        } else {
            $date_object = Carbon::make($date);
        }
        if ($date_object) {
            if ($withoutTime) {
                return $date_object->format('d.m.Y');
            } elseif ($withoutSeconds) {
                return $date_object->format('d.m.Y H:i');
            }
            return $date_object->format('d.m.Y H:i:s');
        }
    } catch (Throwable) {
    }

    return '';
}

if (! function_exists('array_order_by')) {
    /**
     * For Example:
     * array_order_by($array, 'keyName', SORT_NATURAL | SORT_FLAG_CASE)
     *
     * @return mixed|null
     */
    function array_order_by(): mixed
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}

function asset(string $url, bool $versionStamp = false): string
{
    $url = ltrim($url, '/');
    $defaultTheme = config('johncms.skindef');
    foreach ([$defaultTheme, 'default', 'admin'] as $skin) {
        $file = (string) realpath(ASSETS_PATH . $skin . '/' . $url);
        $resultUrl = Str::after(realpath($file), realpath(PUBLIC_PATH));
        if (is_file($file)) {
            return $versionStamp ? $resultUrl . '?v=' . filemtime($file) : $resultUrl;
        }
    }

    throw new InvalidArgumentException('Unable to locate the asset: ' . $url);
}

function viteAssets(string $url): string
{
    return (new \Johncms\View\ViteAssets())->viteAssets($url);
}
