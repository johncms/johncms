<?php
/**
 * JohnCMS Content Management System (https://johncms.com)
 *
 * For copyright and license information, please see the LICENSE
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        https://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
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
            $file = fopen($_SERVER['DOCUMENT_ROOT'].'/files/logs/d.log', "a");
            if ($file) {
                fputs($file, print_r($var, true)."\r\n");
                fclose($file);
            }
        }

        if (!$to_file || $to_file == 2) {
            echo '<pre>'.print_r($var, true).'</pre>';
        }

    }
}

