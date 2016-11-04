<?php

defined('_IN_JOHNCMS') or die('Restricted access');

class core
{
    public static $ip;                        // Путь к корневой папке
    public static $ip_via_proxy = 0;          // IP адрес за прокси-сервером
    public static $ip_count = [];             // Счетчик обращений с IP адреса
    public static $user_agent;                // User Agent
    public static $system_set;                // Системные настройки
    public static $lng_iso = 'en';            // Двухбуквенный ISO код языка
    public static $lng_list = [];             // Список имеющихся языков
    public static $lng = [];                  // Массив с фразами языка
    public static $deny_registration = false; // Запрет регистрации пользователей
    public static $core_errors = [];          // Ошибки ядра

    public static $user_id = false;           // Идентификатор пользователя
    public static $user_rights = 0;           // Права доступа
    public static $user_data = [];            // Все данные пользователя
    public static $user_set = [];             // Пользовательские настройки
    public static $user_ban = [];             // Бан

    private $flood_chk = 1;                   // Включение - выключение функции IP антифлуда
    private $flood_interval = '120';          // Интервал времени в секундах
    private $flood_limit = '70';              // Число разрешенных запросов за интервал

    /**
     * @var Interop\Container\ContainerInterface
     */
    private $container;

    /**
     * @var PDO
     */
    private $db;

    private $config;

    function __construct()
    {
        $this->container = App::getContainer();

        /** @var Johncms\Environment $env */
        $env = $this->container->get('env');
        self::$ip = $env->getIp();
        self::$ip_via_proxy = $env->getIpViaProxy();
        self::$user_agent = $env->getUserAgent();

        // Проверка адреса IP на флуд
        $this->ip_flood();

        // Получаем глобальную конфигурацию
        $this->config = $this->container->get('config');

        // Получаем объект PDO
        $this->db = $this->container->get(PDO::class);

        // Проверяем адрес IP на бан
        $this->checkIpBan();

        // Получаем системные настройки
        self::$system_set = $this->container->get('config')['johncms'];
        self::$lng_iso = self::$system_set['lng'];

        // Автоочистка системы
        //TODO: перенести после авторизации и добавить чистку данных пользователей
        $this->auto_clean();

        // Авторизация пользователей
        $this->authorize();

        // Определяем язык системы
        $this->lng_detect();

        // Загружаем язык
        self::$lng = self::load_lng();
    }

    /**
     * Валидация IP адреса
     *
     * @param string $ip
     * @return bool
     */
    public static function ip_valid($ip)
    {
        if (preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $ip)) {
            return true;
        }

        return false;
    }

    /**
     * Загружаем фразы языка из файла
     *
     * @param string      $module
     * @param null|string $lng
     * @return array|bool
     */
    public static function load_lng($module = '_core', $lng = null)
    {
        $lng_set = $lng !== null && in_array($lng, self::$lng_list) ? $lng : self::$lng_iso;

        if (!is_dir(ROOT_PATH . 'incfiles/languages/' . $lng_set)) {
            self::$lng_iso = 'en';
        }

        $lng_file = ROOT_PATH . 'incfiles/languages/' . $lng_set . '/' . $module . '.lng';
        $lng_file_edit = ROOT_PATH . 'files/lng_edit/' . $lng_set . '_iso.lng';

        if (file_exists($lng_file)) {
            $out = parse_ini_file($lng_file) or die('ERROR: language file');

            if (file_exists($lng_file_edit)) {
                $lng_edit = parse_ini_file($lng_file_edit, true);

                if (isset($lng_edit[$module])) {
                    $lng_module = array_diff_key($out, $lng_edit[$module]);
                    $out = $lng_module + $lng_edit[$module];
                }
            }

            return $out;
        }

        self::$core_errors[] = 'Language file <b>' . $module . '.lng</b> is missing';

        return false;
    }

    /**
     * Проверка адреса IP на флуд
     */
    private function ip_flood()
    {
        if ($this->flood_chk) {
            //if ($this->ip_whitelist(self::$ip))
            //    return true;
            $file = ROOT_PATH . 'files/cache/ip_flood.dat';
            $tmp = [];
            $requests = 1;

            if (!file_exists($file)) {
                $in = fopen($file, "w+");
            } else {
                $in = fopen($file, "r+");
            }

            flock($in, LOCK_EX) or die("Cannot flock ANTIFLOOD file.");
            $now = time();

            while ($block = fread($in, 8)) {
                $arr = unpack("Lip/Ltime", $block);

                if (($now - $arr['time']) > $this->flood_interval) {
                    continue;
                }

                if ($arr['ip'] == self::$ip) {
                    $requests++;
                }

                $tmp[] = $arr;
                self::$ip_count[] = $arr['ip'];
            }

            fseek($in, 0);
            ftruncate($in, 0);

            for ($i = 0; $i < count($tmp); $i++) {
                fwrite($in, pack('LL', $tmp[$i]['ip'], $tmp[$i]['time']));
            }

            fwrite($in, pack('LL', self::$ip, $now));
            fclose($in);

            if ($requests > $this->flood_limit) {
                die('FLOOD: exceeded limit of allowed requests');
            }
        }
    }

    /**
     * Проверяем адрес IP на Бан
     */
    private function checkIpBan()
    {
        $req = $this->db->query("SELECT `ban_type`, `link` FROM `cms_ban_ip`
            WHERE '" . self::$ip . "' BETWEEN `ip1` AND `ip2`
            " . (self::$ip_via_proxy ? " OR '" . self::$ip_via_proxy . "' BETWEEN `ip1` AND `ip2`" : "") . "
            LIMIT 1
        ") or die('Error: table "cms_ban_ip"');

        if ($req->rowCount()) {
            $res = $req->fetch();

            switch ($res['ban_type']) {
                case 2:
                    if (!empty($res['link'])) {
                        header('Location: ' . $res['link']);
                    } else {
                        header('Location: http://johncms.com');
                    }
                    exit;
                    break;
                case 3:
                    self::$deny_registration = true;
                    break;
                default :
                    header("HTTP/1.0 404 Not Found");
                    exit;
            }
        }
    }

    /**
     * Определяем язык
     */
    private function lng_detect()
    {
        $setlng = isset($_POST['setlng']) ? substr(trim($_POST['setlng']), 0, 2) : '';

        if (!empty($setlng) && array_key_exists($setlng, self::$lng_list)) {
            $_SESSION['lng'] = $setlng;
        }

        if (isset($_SESSION['lng']) && array_key_exists($_SESSION['lng'], self::$lng_list)) {
            self::$lng_iso = $_SESSION['lng'];
        } elseif (self::$user_id && isset(self::$user_set['lng']) && array_key_exists(self::$user_set['lng'], self::$lng_list)) {
            self::$lng_iso = self::$user_set['lng'];
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept = explode(',', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));

            foreach ($accept as $var) {
                $lng = substr($var, 0, 2);

                if (array_key_exists($lng, self::$lng_list)) {
                    self::$lng_iso = $lng;
                    break;
                }
            }
        }
    }

    /**
     * Авторизация пользователя и получение его данных из базы
     */
    private function authorize()
    {
        $user_id = false;
        $user_ps = false;

        if (isset($_SESSION['uid']) && isset($_SESSION['ups'])) {
            // Авторизация по сессии
            $user_id = abs(intval($_SESSION['uid']));
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
                $user_data = $req->fetch();
                $permit = $user_data['failed_login'] < 3 || $user_data['failed_login'] > 2 && $user_data['ip'] == self::$ip && $user_data['browser'] == self::$user_agent ? true : false;

                if ($permit && $user_ps === $user_data['password']) {
                    // Если авторизация прошла успешно
                    self::$user_id = $user_data['preg'] ? $user_id : false;
                    self::$user_rights = $user_data['rights'];
                    self::$user_data = $user_data;
                    self::$user_set = !empty($user_data['set_user']) ? unserialize($user_data['set_user']) : $this->user_setings_default();
                    $this->user_ip_history();
                    $this->user_ban_check();
                } else {
                    // Если авторизация не прошла
                    $this->db->query("UPDATE `users` SET `failed_login` = '" . ($user_data['failed_login'] + 1) . "' WHERE `id` = '" . $user_data['id'] . "'");
                    $this->user_unset();
                }
            } else {
                // Если пользователь не существует
                $this->user_unset();
            }
        } else {
            // Для неавторизованных, загружаем настройки по-умолчанию
            self::$user_set = $this->user_setings_default();
        }
    }

    /**
     * Проверка пользователя на Бан
     */
    private function user_ban_check()
    {
        $req = $this->db->query("SELECT * FROM `cms_ban_users` WHERE `user_id` = '" . self::$user_id . "' AND `ban_time` > '" . time() . "'");

        if ($req->rowCount()) {
            self::$user_rights = 0;

            while ($res = $req->fetch()) {
                self::$user_ban[$res['ban_type ']] = 1;
            }
        }
    }

    /**
     * Фиксация истории IP адресов пользователя
     */
    private function user_ip_history()
    {
        if (self::$user_data['ip'] != self::$ip || self::$user_data['ip_via_proxy'] != self::$ip_via_proxy) {
            // Удаляем из истории текущий адрес (если есть)
            $this->db->exec("DELETE FROM `cms_users_iphistory`
                WHERE `user_id` = '" . self::$user_id . "'
                AND `ip` = '" . self::$ip . "'
                AND `ip_via_proxy` = '" . self::$ip_via_proxy . "'
                LIMIT 1
            ");

            if (!empty(self::$user_data['ip']) && self::ip_valid(long2ip(self::$user_data['ip']))) {
                // Вставляем в историю предыдущий адрес IP
                $this->db->exec("INSERT INTO `cms_users_iphistory` SET
                    `user_id` = '" . self::$user_id . "',
                    `ip` = '" . self::$user_data['ip'] . "',
                    `ip_via_proxy` = '" . self::$user_data['ip_via_proxy'] . "',
                    `time` = '" . self::$user_data['lastdate'] . "'
                ");
            }

            // Обновляем текущий адрес в таблице `users`
            $this->db->exec("UPDATE `users` SET
                `ip` = '" . self::$ip . "',
                `ip_via_proxy` = '" . self::$ip_via_proxy . "'
                WHERE `id` = '" . self::$user_id . "'
            ");
        }
    }

    /**
     * Пользовательские настройки по умолчанию
     *
     * @return array
     */
    private function user_setings_default()
    {
        return [
            'avatar'     => 1, // Показывать аватары
            'digest'     => 0, // Показывать Дайджест
            'direct_url' => 0, // Внешние ссылки
            'field_h'    => 3, // Высота текстового поля ввода
            'field_w'    => 40, // Ширина текстового поля ввода
            'kmess'      => 20, // Число сообщений на страницу
            'quick_go'   => 1, // Быстрый переход
            'timeshift'  => 0, // Временной сдвиг
            'skin'       => self::$system_set['skindef'], // Тема оформления
            'smileys'    => 1, // Включить(1) выключить(0) смайлы
            'translit'   => 0 // Транслит
        ];
    }

    /**
     * Уничтожаем данные авторизации юзера
     */
    private function user_unset()
    {
        self::$user_id = false;
        self::$user_rights = 0;
        self::$user_set = $this->user_setings_default();
        self::$user_data = [];
        unset($_SESSION['uid']);
        unset($_SESSION['ups']);
        setcookie('cuid', '');
        setcookie('cups', '');
    }

    /**
     * Автоочистка системы
     */
    private function auto_clean()
    {
        //TODO: переделать очистку
//        if (self::$system_set['clean_time'] < time() - 86400) {
//            $this->db->exec("DELETE FROM `cms_sessions` WHERE `lastdate` < '" . (time() - 86400) . "'");
//            $this->db->exec("DELETE FROM `cms_users_iphistory` WHERE `time` < '" . (time() - 2592000) . "'");
//            $this->db->exec("UPDATE `cms_settings` SET  `val` = '" . time() . "' WHERE `key` = 'clean_time' LIMIT 1");
//            $this->db->query("OPTIMIZE TABLE `cms_sessions` , `cms_users_iphistory`, `cms_mail`, `cms_contact`");
//        }
    }
}
