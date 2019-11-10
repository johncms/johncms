<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Zend\I18n\Translator\Translator;

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
        $translator = App::getContainer()->get(Translator::class);
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
        $translator = App::getContainer()->get(Translator::class);
    }

    return $translator->translatePlural($singular, $plural, $number, $textDomain);
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
            $file = fopen(DATA_PATH . 'debug.log', "a");
            if ($file) {
                fputs($file, print_r($var, true) . "\r\n");
                fclose($file);
            }
        }
        if (! $to_file || $to_file == 2) {
            echo '<pre>' . print_r($var, true) . '</pre>';
        }
    }
}
