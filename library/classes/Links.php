<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Library;

/**
 * Класс помошник формирования ссылок для тегов
 * Class Links
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Links
{
    /**
     * query string для ссылок
     * @var string
     */
    private $link_url;

    /**
     * Массив
     * @var array
     */
    private $in;

    private $res;

    /**
     * @var PDO $db
     */
    private $db;

    /**
     * @var \Johncms\Api\ToolsInterface
     */
    private $tools;

    public function __construct($in, $link_url = '?act=tags&amp;tag=')
    {
        $this->link_url = $link_url;
        $this->in = $in;
        $container = \App::getContainer();
        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get(\Johncms\Api\ToolsInterface::class);
    }

    /**
     * Метод для подготовки ссылок
     * @param $tpl Имя метода для подготовки ссылок
     * @return $this|bool
     */
    public function proccess($tpl)
    {
        if ($this->in) {
            $this->res = array_map([$this, $tpl], $this->in);

            return $this;
        }

        return false;
    }

    /**
     * Метод для обычных ссылок
     * @param string $n
     * @return string
     */
    private function tplTag($n)
    {
        return '<a href="' . $this->link_url . $n . '">' . $this->tools->checkout($n) . '</a>';
    }

    /**
     * Метод для ссылок облака
     * @param string $n
     * @return string
     */
    private function tplCloud($n)
    {
        return '<a href="' . $this->link_url . $this->tools->checkout($n['name']) . '"><span style="font-size: ' . $n['rang'] . 'em;">' . $this->tools->checkout($n['name']) . '</span></a>';
    }

    /**
     * Добавление разделителя ссылкам
     * @param string $sepatator разделитель
     * @return $this|bool
     */
    public function linkSeparator($sepatator = ' | ')
    {
        if ($this->in) {
            $this->res = implode($sepatator, $this->res ? $this->res : $this->in);

            return $this;
        }

        return false;
    }

    /**
     * Получение результата
     * @return string
     */
    public function result()
    {
        return $this->res;
    }

    /**
     * Получение массива
     * @return array
     */
    public function getIn()
    {
        return $this->in;
    }
}
