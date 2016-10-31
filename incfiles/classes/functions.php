<?php

defined('_IN_JOHNCMS') or die('Restricted access');

class functions extends core
{
    /**
     * Показываем местоположение пользователя
     *
     * @param int    $user_id
     * @param string $place
     * @return mixed|string
     */
    public static function display_place($user_id = 0, $place = '')
    {
        global $headmod;

        $homeurl = App::getContainer()->get('config')['johncms']['homeurl'];
        $place = explode(",", $place);
        $placelist = []; //TODO: Написать список местоположений

        if (array_key_exists($place[0], $placelist)) {
            if ($place[0] == 'profile') {
                if ($place[1] == $user_id) {
                    return '<a href="' . $homeurl . '/profile/?user=' . $place[1] . '">' . $placelist['profile_personal'] . '</a>';
                } else {
                    $user = self::get_user($place[1]);

                    return $placelist['profile'] . ': <a href="' . $homeurl . '/profile/?user=' . $user['id'] . '">' . $user['name'] . '</a>';
                }
            } elseif ($place[0] == 'online' && isset($headmod) && $headmod == 'online') {
                return $placelist['here'];
            } else {
                return str_replace('#home#', $homeurl, $placelist[$place[0]]);
            }
        }

        return '<a href="' . $homeurl . '/index.php">' . $placelist['homepage'] . '</a>';
    }

    /**
     * Получаем данные пользователя
     *
     * @param int $id Идентификатор пользователя
     * @return array|bool
     */
    public static function get_user($id = 0)
    {
        if ($id && $id != self::$user_id) {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);
            $req = $db->query("SELECT * FROM `users` WHERE `id` = '$id'");

            if ($req->rowCount()) {
                return $req->fetch();
            } else {
                return false;
            }
        } else {
            return self::$user_data;
        }
    }

    public static function image($name, $args = [])
    {
        $homeurl = App::getContainer()->get('config')['johncms']['homeurl'];

        if (is_file(ROOT_PATH . 'theme/' . core::$user_set['skin'] . '/images/' . $name)) {
            $src = $homeurl . '/theme/' . core::$user_set['skin'] . '/images/' . $name;
        } elseif (is_file(ROOT_PATH . 'images/' . $name)) {
            $src = $homeurl . '/images/' . $name;
        } else {
            return false;
        }

        return '<img src="' . $src . '" alt="' . (isset($args['alt']) ? $args['alt'] : '') . '"' .
        (isset($args['width']) ? ' width="' . $args['width'] . '"' : '') .
        (isset($args['height']) ? ' height="' . $args['height'] . '"' : '') .
        ' class="' . (isset($args['class']) ? $args['class'] : 'icon') . '"/>';
    }

    /**
     * Проверка на игнор у получателя
     *
     * @param $id
     * @return bool
     */
    public static function is_ignor($id)
    {
        static $user_id = null;
        static $return = false;

        if (!self::$user_id && !$id) {
            return false;
        }

        if (is_null($user_id) || $id != $user_id) {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);
            $user_id = $id;
            $req = $db->query("SELECT * FROM `cms_contact` WHERE `user_id` = '$id' AND `from_id` = '" . self::$user_id . "'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($res['ban'] == 1) {
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Транслитерация с Русского в латиницу
     *
     * @param string $str
     * @return string
     */
    public static function rus_lat($str)
    {
        $replace = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'j',
            'з' => 'z',
            'и' => 'i',
            'й' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => "",
            'ы' => 'y',
            'ь' => "",
            'э' => 'ye',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        return strtr($str, $replace);
    }

    /**
     * Функция пересчета на дни, или часы
     *
     * @param int $var
     * @return bool|string
     */
    public static function timecount($var)
    {
        global $lng;
        if ($var < 0) {
            $var = 0;
        }
        $day = ceil($var / 86400);
        if ($var > 345600) {
            return $day . ' ' . $lng['timecount_days'];
        }
        if ($var >= 172800) {
            return $day . ' ' . $lng['timecount_days_r'];
        }
        if ($var >= 86400) {
            return '1 ' . $lng['timecount_day'];
        }

        return date("G:i:s", mktime(0, 0, $var));
    }

    // Транслитерация текста
    public static function trans($str)
    {
        $replace = [
            'a'  => 'а',
            'b'  => 'б',
            'v'  => 'в',
            'g'  => 'г',
            'd'  => 'д',
            'e'  => 'е',
            'yo' => 'ё',
            'zh' => 'ж',
            'z'  => 'з',
            'i'  => 'и',
            'j'  => 'й',
            'k'  => 'к',
            'l'  => 'л',
            'm'  => 'м',
            'n'  => 'н',
            'o'  => 'о',
            'p'  => 'п',
            'r'  => 'р',
            's'  => 'с',
            't'  => 'т',
            'u'  => 'у',
            'f'  => 'ф',
            'h'  => 'х',
            'c'  => 'ц',
            'ch' => 'ч',
            'w'  => 'ш',
            'sh' => 'щ',
            'q'  => 'ъ',
            'y'  => 'ы',
            'x'  => 'э',
            'yu' => 'ю',
            'ya' => 'я',
            'A'  => 'А',
            'B'  => 'Б',
            'V'  => 'В',
            'G'  => 'Г',
            'D'  => 'Д',
            'E'  => 'Е',
            'YO' => 'Ё',
            'ZH' => 'Ж',
            'Z'  => 'З',
            'I'  => 'И',
            'J'  => 'Й',
            'K'  => 'К',
            'L'  => 'Л',
            'M'  => 'М',
            'N'  => 'Н',
            'O'  => 'О',
            'P'  => 'П',
            'R'  => 'Р',
            'S'  => 'С',
            'T'  => 'Т',
            'U'  => 'У',
            'F'  => 'Ф',
            'H'  => 'Х',
            'C'  => 'Ц',
            'CH' => 'Ч',
            'W'  => 'Ш',
            'SH' => 'Щ',
            'Q'  => 'Ъ',
            'Y'  => 'Ы',
            'X'  => 'Э',
            'YU' => 'Ю',
            'YA' => 'Я',
        ];

        return strtr($str, $replace);
    }
}
