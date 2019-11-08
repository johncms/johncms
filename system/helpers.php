<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * Обёртка над функцией print_r
 *
 * @param mixed $var
 * @param bool $to_file
 */
if (!function_exists('d')) {
    function d($var = false, $to_file = false)
    {
        if ($to_file) {
            $file = fopen(DATA_PATH . 'debug.log', "a");
            if ($file) {
                fputs($file, print_r($var, true) . "\r\n");
                fclose($file);
            }
        }
        if (!$to_file || $to_file == 2) {
            echo '<pre>' . print_r($var, true) . '</pre>';
        }
    }
}