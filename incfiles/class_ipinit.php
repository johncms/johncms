<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Restricted access');

class ipinit {
    public $ip;    // IP адрес в LONG формате
    public $flood_chk = 1;    // Включение - выключение функции IP антифлуда
    public $flood_interval = '120';    // Интервал времени в секундах
    public $flood_limit = '50';    // Число разрешенных запросов за интервал
    public $flood_file = 'http_antiflood.dat';    // Рабочий файл функции
    private $requests;    // Число запросов с IP адреса за период времени

    function __construct() {
        $this->ip = ip2long($this->getip());
        // Проверка адреса IP на HTTP флуд
        if ($this->flood_chk) {
            $this->requests = $this->reqcount();
            if ($this->requests > $this->flood_limit)
                die('Flood!!!');
        }
    }

    // Получаем реальный адрес IP
    private function getip() {
        if (isset ($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
            return $ip[sizeof($ip) - 1];
        }
        elseif ($_SERVER['REMOTE_ADDR']) {
            return $_SERVER['REMOTE_ADDR'];
        }
        else {
            die('Unknown IP');
        }
    }

    // Счетчик числа обращений с данного IP
    private function reqcount() {
        global $rootpath;
        $tmp = array();
        $requests = 1;
        if (!file_exists($rootpath . 'cache/' . $this->flood_file))
            $in = fopen($rootpath . 'cache/' . $this->flood_file, "w+");
        else
            $in = fopen($rootpath . 'cache/' . $this->flood_file, "r+");
        flock($in, LOCK_EX) or die("Cannot flock ANTIFLOOD file.");
        $now = time();
        while ($block = fread($in, 8)) {
            $arr = unpack("Lip/Ltime", $block);
            if (($now - $arr['time']) > $this->flood_interval) {
                continue;
            }
            if ($arr['ip'] == $this->ip) {
                $requests++;
            }
            $tmp[] = $arr;
        }
        fseek($in, 0);
        ftruncate($in, 0);
        for ($i = 0; $i < count($tmp); $i++) {
            fwrite($in, pack('LL', $tmp[$i]['ip'], $tmp[$i]['time']));
        }
        fwrite($in, pack('LL', $this->ip, $now));
        fclose($in);
        return $requests;
    }
}

?>