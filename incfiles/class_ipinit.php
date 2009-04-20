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

class ipinit
{
    public $ip; // IP адрес в LONG формате
    public $flood_chk = '0'; // Включение - выключение функции IP антифлуда
    public $flood_interval = '60'; // Интервал времени
    public $flood_limit = '20'; // Число разрешенных запросов за интервал
    public $flood_file = 'flood.dat'; // Рабочий файл функции
    private $requests; // Число запросов с IP адреса за период времени

    function __construct()
    {
        $this->ip = $this->getip();
        // Проверка адреса IP на HTTP флуд
        if ($this->flood_chk)
        {
            $this->requests = reqcount();
            if ($this->requests > $this->flood_limit)
                die('Flood!!!');
        }
    }

    // Получаем реальный адрес IP
    private function getip()
    {
        $ip1 = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ip2long($_SERVER['HTTP_X_FORWARDED_FOR']) : false;
        $ip2 = isset($_SERVER['HTTP_VIA']) ? ip2long($_SERVER['HTTP_VIA']) : false;
        $ip3 = isset($_SERVER['REMOTE_ADDR']) ? ip2long($_SERVER['REMOTE_ADDR']) : false;
        if ($ip1 && $ip1 > 184549376)
        {
            return $ip1;
        } elseif ($ip2 && $ip2 > 184549376)
        {
            return $ip2;
        } elseif ($ip3)
        {
            return $ip3;
        } else
        {
            die('Unknown IP');
        }
    }

    // Счетчик числа обращений с данного IP
	private function reqcount()
    {
        global $rootpath;
        $tmp = array();
        $requests = 1;
        $in = fopen($rootpath . $this->flood_file, "r+");
        flock($in, LOCK_EX) or die("Cannot flock ANTIFLOOD file.");
        $now = time();
        while ($block = fread($in, 8))
        {
            $arr = unpack("Lip/Ltime", $block);
            if (($now - $arr['time']) > $this->flood_interval)
            {
                continue;
            }
            if ($arr['ip'] == $this->ip)
            {
                $requests++;
            }
            $tmp[] = $arr;
        }
        fseek($in, 0);
        ftruncate($in, 0);
        for ($i = 0; $i < count($tmp); $i++)
        {
            fwrite($in, pack('LL', $tmp[$i]['ip'], $tmp[$i]['time']));
        }
        fwrite($in, pack('LL', $this->ip, $now));
        fclose($in);
        return $requests;
    }
}

?>