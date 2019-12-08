<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\View\Render;
use Zend\I18n\Translator\Translator;

function di(string $service)
{
    return App::getContainer()->get($service);
}

/**
 * Translate a message
 *
 * @param string $message
 * @param string $textDomain
 * @return string
 */
function _t(string $message, string $textDomain = 'default') : string
{
    /** @var Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = di(Translator::class);
    }

    return $translator->translate($message, $textDomain);
}

/**
 * Translate a plural message
 *
 * @param string $singular
 * @param string $plural
 * @param int    $number
 * @param string $textDomain
 * @return string
 */
function _p(string $singular, string $plural, int $number, string $textDomain = 'default') : string
{
    /** @var Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = di(Translator::class);
    }

    return $translator->translatePlural($singular, $plural, $number, $textDomain);
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
) : void {
    $engine = di(Render::class);

    if (! headers_sent()) {
        header('HTTP/1.0 404 Not Found');
    }

    exit($engine->render($template, [
        'title'   => $title,
        'message' => ! empty($message)
            ? $message
            : _t('You are looking for something that doesn\'t exist or may have moved'),
    ]));
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
 * @param bool  $to_file
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
