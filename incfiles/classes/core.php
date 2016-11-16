<?php

defined('_IN_JOHNCMS') or die('Restricted access');

class core
{
    public static $system_set;                //TODO: Удалить
    public static $lng_iso = 'en';            // Двухбуквенный ISO код языка
    public static $lng_list = [];             // Список имеющихся языков
    public static $deny_registration = false; // Запрет регистрации пользователей

    public static $user_id = false;           //TODO: Удалить
    public static $user_data = [];            //TODO: Удалить
    public static $user_set = [];             // Пользовательские настройки

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

        // Получаем глобальную конфигурацию
        $this->config = $this->container->get('config');

        // Получаем объект PDO
        $this->db = $this->container->get(PDO::class);

        // Получаем системные настройки
        self::$system_set = $this->container->get('config')['johncms'];
        self::$lng_iso = self::$system_set['lng'];

        // Автоочистка системы
        //TODO: перенести после авторизации и добавить чистку данных пользователей
        $this->auto_clean();

        // Определяем язык системы
        $this->lng_detect();
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
