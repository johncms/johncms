<?php

namespace Johncms;

use Interop\Container\ContainerInterface;
use Zend\Stdlib\ArrayUtils;

class UserFactory
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var Environment
     */
    private $env;

    private $userData;

    public function __invoke(ContainerInterface $container)
    {
        $this->db = $container->get(\PDO::class);
        $this->env = $container->get('env');
        $this->userData = $this->authorize();

        return new User($this->userData, User::ARRAY_AS_PROPS);
    }

    /**
     * Авторизация пользователя и получение его данных из базы
     */
    private function authorize()
    {
        $user_id = false;
        $user_ps = false;
        $userData = $this->userTemplate();

        if (isset($_SESSION['uid']) && isset($_SESSION['ups'])) {
            // Авторизация по сессии
            $user_id = intval($_SESSION['uid']);
            $user_ps = $_SESSION['ups'];
        } elseif (isset($_COOKIE['cuid']) && isset($_COOKIE['cups'])) {
            // Авторизация по COOKIE
            $user_id = abs(intval(base64_decode(trim($_COOKIE['cuid']))));
            $_SESSION['uid'] = $user_id;
            $user_ps = md5(trim($_COOKIE['cups']));
            $_SESSION['ups'] = $user_ps;
        }

        if ($user_id && $user_ps) {
            $req = $this->db->query('SELECT * FROM `users` WHERE `id` = ' . $user_id);

            if ($req->rowCount()) {
                $data = $req->fetch();
                $permit = $data['failed_login'] < 3
                || $data['failed_login'] > 2
                && $data['ip'] == $this->env->getIp()
                && $data['browser'] == $this->env->getUserAgent()
                    ? true
                    : false;

                if ($permit && $user_ps === $data['password']) {
                    // Если авторизация прошла успешно
                    if ($data['preg']) {
                        //$this->user_ip_history();
                        //$this->user_ban_check();
                        $userData = ArrayUtils::merge($userData, $data);
                    }
                } else {
                    // Если авторизация не прошла
                    $this->db->query("UPDATE `users` SET `failed_login` = '" . ($data['failed_login'] + 1) . "' WHERE `id` = '" . $data['id'] . "'");
                    $this->userUnset();
                }
            } else {
                // Если пользователь не существует
                $this->userUnset();
            }
        }

        return $userData;
    }

    private function userTemplate()
    {
        $template = [
            'id'            => 0,
            'name'          => '',
            'name_lat'      => '',
            'password'      => '',
            'rights'        => 0,
            'failed_login'  => 0,
            'imname'        => '',
            'sex'           => '',
            'komm'          => 0,
            'postforum'     => 0,
            'postguest'     => 0,
            'yearofbirth'   => 0,
            'datereg'       => 0,
            'lastdate'      => 0,
            'mail'          => '',
            'icq'           => '',
            'skype'         => '',
            'jabber'        => '',
            'www'           => '',
            'about'         => '',
            'live'          => '',
            'mibile'        => '',
            'status'        => '',
            'ip'            => '',
            'ip_via_proxy'  => '',
            'browser'       => '',
            'preg'          => '',
            'regadm'        => '',
            'mailvis'       => '',
            'dayb'          => '',
            'monthb'        => '',
            'sestime'       => '',
            'total_on_site' => '',
            'lastpost'      => '',
            'rest_code'     => '',
            'rest_time'     => '',
            'movings'       => '',
            'place'         => '',
            'set_user'      => '',
            'set_forum'     => '',
            'set_mail'      => '',
            'karma_plus'    => '',
            'karma_minus'   => '',
            'karma_time'    => '',
            'karma_off'     => '',
            'comm_count'    => '',
            'comm_old'      => '',
            'smileys'       => '',
        ];

        return $template;
    }

    /**
     * Уничтожаем данные авторизации юзера
     */
    private function userUnset()
    {
        unset($_SESSION['uid']);
        unset($_SESSION['ups']);
        setcookie('cuid', '');
        setcookie('cups', '');
    }
}
