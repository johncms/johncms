<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Api;

interface NavChainInterface
{
    /**
     * Метод добавляет элемент в навигационную цепочку
     *
     * @param string $name - Название
     * @param string $url
     * @return mixed
     */
    public function add(string $name, string $url = '');

    /**
     * Получение списка элементов навигационной цепочки
     *
     * @return array
     */
    public function getAll(): array;
}
