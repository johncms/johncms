<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class Tools
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \PDO
     */
    private $db;

    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get('config')['johncms'];
        $this->db = $container->get(\PDO::class);

        return $this;
    }

    public function antiflood(array $userData)
    {
        $config = unserialize($this->config['antiflood']);

        switch ($config['mode']) {
            // Адаптивный режим
            case 1:
                $adm = $this->db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > ' . (time() - 300))->fetchColumn();
                $limit = $adm > 0 ? $config['day'] : $config['night'];
                break;
            // День
            case 3:
                $limit = $config['day'];
                break;
            // Ночь
            case 4:
                $limit = $config['night'];
                break;
            // По умолчанию день / ночь
            default:
                $c_time = date('G', time());
                $limit = $c_time > $config['day'] && $c_time < $config['night'] ? $config['day'] : $config['night'];
        }

        // Для Администрации задаем лимит в 4 секунды
        if ($userData['rights'] > 0) {
            $limit = 4;
        }

        $flood = $userData['lastpost'] + $limit - time();

        return $flood > 0 ? $flood : false;
    }

    /**
     * Обработка текстов перед выводом на экран
     *
     * @param string $str
     * @param int    $br   Параметр обработки переносов строк
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вместо переносов строки вставляются пробелы
     * @param int    $tags Параметр обработки тэгов
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вырезать тэги
     *
     * @return string
     */
    public function checkout($str, $br = 0, $tags = 0)
    {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');

        if ($br == 1) {
            $str = nl2br($str);
        } elseif ($br == 2) {
            $str = str_replace("\r\n", ' ', $str);
        }

        if ($tags == 1) {
            $str = $this->container->get('bbcode')->tags($str);
        } elseif ($tags == 2) {
            $str = $this->container->get('bbcode')->notags($str);
        }

        return trim($str);
    }
}
