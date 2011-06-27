<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Restricted access');

class ad
{
    /*
    -----------------------------------------------------------------
    Рекламная сеть ADMOB.COM
    -----------------------------------------------------------------
    */
    public static function admob()
    {
        $admob_params = array(
            'PUBLISHER_ID' => 'a14da6cc17f1c50', //TODO: Задать идентификатор из настроек
            'ANALYTICS_ID' => '' //TODO: Задать идентификатор из настроек
        );
        $ad_mode = false;
        $analytics_mode = false;
        static $pixel_sent = false;
        if (!empty($admob_params['PUBLISHER_ID'])) $ad_mode = true;
        if (!empty($admob_params['ANALYTICS_ID']) && !$pixel_sent) $analytics_mode = true;
        $rt = $ad_mode ? ($analytics_mode ? 2 : 0) : ($analytics_mode ? 1 : -1);
        if ($rt == -1) return '';
        list($usec, $sec) = explode(' ', microtime());
        $params = array('rt=' . $rt,
                        'z=' . ($sec + $usec),
                        'u=' . urlencode($_SERVER['HTTP_USER_AGENT']),
                        'i=' . urlencode($_SERVER['REMOTE_ADDR']),
                        'p=' . urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']),
                        'v=' . urlencode('20081105-PHPFSOCK-33fdd8e59a40dd9a'));

        $sid = empty($admob_params['SID']) ? session_id() : $admob_params['SID'];
        if (!empty($sid)) $params[] = 't=' . md5($sid);
        if ($ad_mode) $params[] = 's=' . $admob_params['PUBLISHER_ID'];
        if ($analytics_mode) $params[] = 'a=' . $admob_params['ANALYTICS_ID'];
        if (!empty($_COOKIE['admobuu'])) $params[] = 'o=' . $_COOKIE['admobuu'];
        $ignore = array('HTTP_PRAGMA' => true, 'HTTP_CACHE_CONTROL' => true, 'HTTP_CONNECTION' => true, 'HTTP_USER_AGENT' => true, 'HTTP_COOKIE' => true);
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 4) == 'HTTP' && empty($ignore[$k]) && isset($v)) {
                $params[] = urlencode('h[' . $k . ']') . '=' . urlencode($v);
            }
        }
        $post = implode('&', $params);
        $request_timeout = 1; // 1 second timeout
        $errno = 0;
        $errstr = '';
        list($usec_start, $sec_start) = explode(' ', microtime());
        $request = @fsockopen('r.admob.com', 80, $errno, $errstr, $request_timeout);
        $out = array();
        if ($request) {
            stream_set_timeout($request, $request_timeout);
            $post_body = "POST /ad_source.php HTTP/1.0\r\nHost: r.admob.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($post) . "\r\n\r\n" . $post;
            $post_body_len = strlen($post_body);
            $bytes_written = 0;
            $body = false;
            $info = stream_get_meta_data($request);
            $timeout = $info['timed_out'];
            while ($bytes_written < $post_body_len && !$timeout) {
                $current_bytes_written = fwrite($request, $post_body);
                if ($current_bytes_written === false) return ''; // write failed
                $bytes_written += $current_bytes_written;
                if ($bytes_written === $post_body_len) break;
                $post_body = substr($post_body, $bytes_written);
                $info = stream_get_meta_data($request);
                $timeout = $info['timed_out'];
            }
            while (!feof($request) && !$timeout) {
                $line = fgets($request);
                if (!$body && $line == "\r\n") $body = true;
                $line = trim($line);
                if ($body && !empty($line)) $out['ads'][] = $line;
                $info = stream_get_meta_data($request);
                $timeout = $info['timed_out'];
            }
            fclose($request);
        }
        if (!$pixel_sent) {
            $pixel_sent = true;
            list($usec_end, $sec_end) = explode(' ', microtime());
            $out['pixel'] = '<img src="http://p.admob.com/e0?' .
                            'rt=' . $rt .
                            '&amp;z=' . ($sec + $usec) .
                            '&amp;a=' . ($analytics_mode ? $admob_params['ANALYTICS_ID'] : '') .
                            '&amp;s=' . ($ad_mode ? $admob_params['PUBLISHER_ID'] : '') .
                            '&amp;o=' . (empty($_COOKIE['admobuu']) ? '' : $_COOKIE['admobuu']) .
                            '&amp;lt=' . ($sec_end + $usec_end - $sec_start - $usec_start) .
                            '&amp;to=' . $request_timeout .
                            '" alt="" width="1" height="1"/>';
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Рекламная сеть MULTICLICK.RU
    -----------------------------------------------------------------
    */
    public static function multiclick()
    {
        $timeout = 100000; // Время ожидания в микросекундах
        $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$sock) return false;
        if (!@socket_bind($sock, 0, 0)) return false;
        $ip = gethostbyname('show.multiclick.ru');
        if (!$ip) return false;
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 0, "usec" => $timeout));
        socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 0, "usec" => $timeout));
        if (!@socket_connect($sock, $ip, 80)) return false;

        // Задаем аргументы для запроса
        //TODO: Задать идентификатор из настроек
        $arg[] = 'placement=4072';
        $arg[] = 'charset=UTF-8';
        $arg[] = 'version=20080811-PHP_C_S';
        $arg[] = 'ua=' . urlencode($_SERVER["HTTP_USER_AGENT"]);
        $arg[] = 'ip=' . $_SERVER["REMOTE_ADDR"];
        $arg[] = 'ruri=' . urlencode($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $arg[] = 'ip2=' . urlencode($_SERVER["HTTP_X_FORWARDED_FOR"]);
        if (isset($_SERVER["HTTP_X_OPERAMINI_PHONE_UA"]))
            $arg[] = 'miniua=' . urlencode($_SERVER["HTTP_X_OPERAMINI_PHONE_UA"]);
        if (isset($_SERVER["HTTP_X_OPERAMINI_PHONE"]))
            $arg[] = 'miniphone=' . urlencode($_SERVER["HTTP_X_OPERAMINI_PHONE"]);

        // Необязательные аргументы
        $arg[] = 'sid=8320'; // id социальной сети (=id партнера в системе МультиКлик)
        //$arg[] = 'gender=';                    // Пол пользователя m[ale] or f[emale]
        //$arg[] = 'age=';                       // Возраст пользователя от 1 до 99
        //$arg[] = 'region=';                    // Название города или региона
        //$arg[] = 'interest=';                  // Интересы пользователя через запятую
        //$arg[] = 'uid=';                       // Уникальный идентификатор пользователя (не обязан совпадать с оригинальным в системе)

        // Запрашиваем рекламу
        $request = 'GET /show.php?' . implode('&', $arg) . " HTTP/1.0\r\nHost: show.multiclick.ru\r\n\r\n";
        $bytesToWrite = strlen($request);
        while ($bytesToWrite > 0) {
            $bytesWritten = @socket_write($sock, substr($request, -$bytesToWrite));
            if ($bytesWritten === false) {
                @socket_close($sock);
                return false;
            }
            $bytesToWrite -= $bytesWritten;
        }
        $response = '';
        while ($buf = @socket_read($sock, 10240, PHP_BINARY_READ)) $response .= $buf;
        @socket_close($sock);
        $start = strpos($response, "\r\n\r\n");
        if (!$start) $start = strpos($response, "\n\n");
        if (!$start) return false;
        $response = trim(mb_substr($response, $start));

        // Формируем массив с ссылками ['ads'] и пикселем ['pixel']
        $array = explode('<br/>', $response);
        $out = array();
        foreach ($array as $val) {
            if (stristr($val, "show.multiclick.ru/blank.php")) $out['pixel'] = $val;
            elseif (!empty($val)) $out['ad'][] = $val;
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Рекламная сеть mobileads.ru
    -----------------------------------------------------------------
    */
    public static function mobileads()
    {
        // Задаем аргументы для запроса
        $arg[] = 'id=2106'; //TODO: Задать идентификатор из настроек
        $arg[] = 'ip=' . $_SERVER["REMOTE_ADDR"];
        $arg[] = 'ua=' . urlencode($_SERVER["HTTP_USER_AGENT"]);
        $arg[] = 'ref=' . urlencode($_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $arg[] = 'xip=' . urlencode($_SERVER["HTTP_X_FORWARDED_FOR"]);

        // Запрашиваем рекламу
        $res = 'empty';
        if (core::$is_mobile) {
            $fp = fsockopen('mobileads.ru', 80, $error, $err_str, 1);
            if ($fp) {
                fwrite($fp, 'GET /links?' . implode('&', $arg) . ' HTTP/1.0' . "\r\n\r\n");
                stream_set_timeout($fp, 1, 1000000);
                $res = fread($fp, 8096);
                fclose($fp);
            }
        }

        // Формируем массив с ссылками ['ads']
        $out = array();
        if ($res != 'empty') {
            $mad_lines = explode("\r\n", $res);
            $n = 7;
            if ($mad_lines > $n)
            for ($malCount = $n; $malCount < count($mad_lines); $malCount += 2) {
                    $linkURL = trim($mad_lines[$malCount]);
                    $linkName = iconv('Windows-1251', 'UTF-8', $mad_lines[$malCount + 1]);
                    $out['ads'][] = '<a href="' . $linkURL . '">' . $linkName . '</a>';
                }
        }
        return $out;
    }
}

?>