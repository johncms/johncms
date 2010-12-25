<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*  Парсер SQL разработан на основе функций CMS Joomla
*/

defined('INSTALL') or die('Error: restricted access');

class parse_sql {
    public $errors = array ();

    /*
    -----------------------------------------------------------------
    Читаем SQL файл и заносим в базу данных
    -----------------------------------------------------------------
    */
    function __construct($file = false) {
        if ($file && file_exists($file)) {
            $query = fread(fopen($file, 'r'), filesize($file));
            $pieces = $this->split_sql($query);
            for ($i = 0; $i < count($pieces); $i++) {
                $pieces[$i] = trim($pieces[$i]);
                if (!empty($pieces[$i]) && $pieces[$i] != "#") {
                    if (!mysql_query($pieces[$i])) {
                        $this->errors[] = mysql_error();
                    }
                }
            }
        } else {
            $this->errors[] = 'Fatal error!';
            return false;
        }
    }

    /*
    -----------------------------------------------------------------
    Парсинг SQL
    -----------------------------------------------------------------
    */
    private function split_sql($sql) {
        $sql = trim($sql);
        $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);
        $buffer = array ();
        $ret = array ();
        $in_string = false;

        for ($i = 0; $i < strlen($sql) - 1; $i++) {
            if ($sql[$i] == ";" && !$in_string) {
                $ret[] = substr($sql, 0, $i);
                $sql = substr($sql, $i + 1);
                $i = 0;
            }
            if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
                $in_string = false;
            }  elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
                $in_string = $sql[$i];
            }
            if (isset($buffer[1])) {
                $buffer[0] = $buffer[1];
            }
            $buffer[1] = $sql[$i];
        }

        if (!empty($sql)) {
            $ret[] = $sql;
        }
        return ($ret);
    }
}
?>