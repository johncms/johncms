<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

class NavChain
{
    /** @var array */
    private $items = [];

    /** @var bool */
    private $show_home_page = true;

    /** @var bool */
    private $last_is_active = true;

    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Добавление элемента в навигационную цепочку
     *
     * @param string $name
     * @param string $url
     * @return mixed|void
     */
    public function add(string $name, string $url = '')
    {
        $this->items[] = [
            'name' => $name,
            'url'  => $url,
        ];
    }

    /**
     * Получение всех элементов навигационной цепочки
     *
     * @return array
     */
    public function getAll(): array
    {
        $all_items = $this->items;

        // Добавляем главную страницу в навигационную цепочку
        if ($this->show_home_page) {
            $home_page_item = [
                'name' => d__('system', 'Home'),
                'url'  => '/',
            ];
            $all_items = array_merge([$home_page_item], $all_items);
        }

        // Помечаем последний элемент активным
        if ($this->last_is_active && ! empty($all_items)) {
            $last_key = array_key_last($all_items);
            $all_items[$last_key]['active'] = true;
        }

        return $all_items;
    }

    /**
     * Добавлять главную страницу в навигационную цепочку
     *
     * @param bool $value
     * @return void
     */
    public function showHomePage(bool $value): void
    {
        $this->show_home_page = $value;
    }

    /**
     * Помечать последний элемент активным
     *
     * @param bool $value
     * @return void
     */
    public function lastIsActive(bool $value): void
    {
        $this->last_is_active = $value;
    }
}
