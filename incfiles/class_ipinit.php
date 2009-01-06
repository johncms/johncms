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
    var $ip; // IP адрес в LONG формате
    var $flood_chk = '0'; // Включение - выключение функции IP антифлуда
    var $flood_interval = '60'; // Интервал времени
    var $flood_limit = '20'; // Число разрешенных запросов за интервал
    var $flood_file = 'flood.dat'; // Рабочий файл функции
    var $requests; // Число запросов с IP адреса за период времени

    function ipinit()
    {
        // Получение реального IP адреса
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ip2long($_SERVER['HTTP_X_FORWARDED_FOR']) != 0)
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_VIA']) && ip2long($_SERVER['HTTP_VIA']) != 0)
        {
            $ip = $_SERVER['HTTP_VIA'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && ip2long($_SERVER['REMOTE_ADDR']) != 0)
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else
        {
            die('Unknown IP');
        }
        $this->ip = ip2long($ip);

        // Проверка адреса IP на HTTP флуд
        if ($this->flood_chk)
        {
            $this->reqcount();
            if ($this->requests > $this->flood_limit)
                die('Flood!!!');
        }
    }

    function reqcount()
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
        $this->requests = $requests;
    }
}

?>