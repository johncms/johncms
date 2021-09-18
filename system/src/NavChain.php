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
    private array $items = [];
    private bool $showHomePage = true;
    private bool $lastIsActive = true;

    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Добавление элемента в навигационную цепочку
     *
     * @param string $name
     * @param string $url
     * @return void
     */
    public function add(string $name, string $url = '')
    {
        $this->items[] = [
            'name' => htmlspecialchars($name),
            'url'  => htmlspecialchars($url),
        ];
    }

    /**
     * Получение всех элементов навигационной цепочки
     *
     * @return array
     */
    public function getAll(): array
    {
        $allItems = $this->items;

        // Добавляем главную страницу в навигационную цепочку
        if ($this->showHomePage) {
            $homePageItem = [
                'name' => d__('system', 'Home'),
                'url'  => '/',
            ];
            $allItems = array_merge([$homePageItem], $allItems);
        }

        // Помечаем последний элемент активным
        if ($this->lastIsActive && ! empty($allItems)) {
            $lastKey = array_key_last($allItems);
            $allItems[$lastKey]['active'] = true;
        }

        return $allItems;
    }

    /**
     * Добавлять главную страницу в навигационную цепочку
     *
     * @param bool $value
     * @return void
     */
    public function showHomePage(bool $value): void
    {
        $this->showHomePage = $value;
    }

    /**
     * Помечать последний элемент активным
     *
     * @param bool $value
     * @return void
     */
    public function lastIsActive(bool $value): void
    {
        $this->lastIsActive = $value;
    }
}
