<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class ToolsFactory
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
}
