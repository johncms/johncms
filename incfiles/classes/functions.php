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
}
