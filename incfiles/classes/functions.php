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
}
