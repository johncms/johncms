<?php

/*
////////////////////////////////////////////////////////////
// MobiCMS  система управления мобильным сайтом           //
// Copyright © 2009 Oleg Kasyanov aka AlkatraZ            //
// E-mail: alkatraz@gazenwagen.com ICQ: 267070            //
////////////////////////////////////////////////////////////
// Официальный сайт сайт проекта:	http://mobicms.net    //
// Дополнительный сайт поддержки:   http://gazenwagen.com //
////////////////////////////////////////////////////////////
// Данный модуль адаптирован для работы с JohnCMS         //
// для модуля действует основная лицензия JohnCMS         //
////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error:restricted access');
define('ROOT_DIR', '..');

class scaner
{
    var $scan_folders = array();
    var $cache_files = array();
    var $good_files = array();
    var $snap_base = 'filebase.dat';

    var $checked_folders = array();
    var $track_files = array();
    var $snap_files = array();
    var $bad_files = array();
    var $snap = false;

    function scan()
    {
        ////////////////////////////////////////////////////////////
        // Сканирование на соответствие дистрибутиву              //
        ////////////////////////////////////////////////////////////
        foreach ($this->scan_folders as $data)
        {
            $this->scan_files(ROOT_DIR . $data);
        }
    }

    function snapscan()
    {
        ////////////////////////////////////////////////////////////
        // Сканирование по образу                                 //
        ////////////////////////////////////////////////////////////
        if (file_exists($this->snap_base))
        {
			$filecontents = file($this->snap_base);
            foreach ($filecontents as $name => $value)
            {
                $filecontents[$name] = explode("|", trim($value));
                $this->track_files[$filecontents[$name][0]] = $filecontents[$name][1];
            }
            $this->snap = true;
        }
        foreach ($this->scan_folders as $data)
        {
            $this->scan_files(ROOT_DIR . $data);
        }
    }

    function snap()
    {
        ////////////////////////////////////////////////////////////
        // Добавляем снимок надежных файлов в базу                //
        ////////////////////////////////////////////////////////////
        foreach ($this->scan_folders as $data)
        {
            $this->scan_files(ROOT_DIR . $data, true);
            //$this->scan_files(ROOT_DIR . $data);
        }
        $filecontents = "";
        foreach ($this->snap_files as $idx => $data)
        {
            $filecontents .= $data['file_path'] . "|" . $data['file_crc'] . "\r\n";
        }
        $filehandle = fopen($this->snap_base, "w+");
        fwrite($filehandle, $filecontents);
        fclose($filehandle);
        @chmod($this->snap_base, 0666);
    }

    function scan_files($dir, $snap = false)
    {
        ////////////////////////////////////////////////////////////
        // Служебная функция сканирования                         //
        ////////////////////////////////////////////////////////////
        if (!isset($file))
            $file = false;
        $this->checked_folders[] = $dir . '/' . $file;
        if ($dh = @opendir($dir))
        {
            while (false !== ($file = readdir($dh)))
            {
                if ($file == '.' or $file == '..' or $file == '.svn' or $file == '.DS_store')
                {
                    continue;
                }
                if (is_dir($dir . '/' . $file))
                {
                    if ($dir != ROOT_DIR)
                        $this->scan_files($dir . '/' . $file, $snap);
                } else
                {
                    if ($this->snap or $snap)
                        $templates = "|tpl";
                    else
                        $templates = "";
                    if (preg_match("#.*\.(php|cgi|pl|perl|php3|php4|php5|php6|phtml|py|htaccess" . $templates . ")$#i", $file))
                    {
                        $folder = str_replace("../..", ".", $dir);
                        $file_size = filesize($dir . '/' . $file);
                        $file_crc = strtoupper(dechex(crc32(file_get_contents($dir . '/' . $file))));
                        $file_date = date("d.m.Y H:i:s", filectime($dir . '/' . $file));
                        if ($snap)
                        {
                            $this->snap_files[] = array('file_path' => $folder . '/' . $file, 'file_crc' => $file_crc);
                        } else
                        {
                            if ($this->snap)
                            {
                                if ($this->track_files[$folder . '/' . $file] != $file_crc and !in_array($folder . '/' . $file, $this->cache_files))
                                    $this->bad_files[] = array('file_path' => $folder . '/' . $file, 'file_name' => $file, 'file_date' => $file_date, 'type' => 1, 'file_size' => $file_size);
                            } else
                            {
                                if (!in_array($folder . '/' . $file, $this->good_files) or $file_size > 110000)
                                    $this->bad_files[] = array('file_path' => $folder . '/' . $file, 'file_name' => $file, 'file_date' => $file_date, 'type' => 0, 'file_size' => $file_size);
                            }
                        }
                    }
                }
            }
        }
    }
}

?>