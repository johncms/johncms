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
        $placelist = [
            'homepage' => 'Home',
        ]; //TODO: Написать список местоположений

        if (array_key_exists($place[0], $placelist)) {
            if ($place[0] == 'profile') {
                if ($place[1] == $user_id) {
                    return '<a href="' . $homeurl . '/profile/?user=' . $place[1] . '">' . $placelist['profile_personal'] . '</a>';
                } else {
                    $user = App::getContainer()->get('tools')->getUser($place[1]);

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
