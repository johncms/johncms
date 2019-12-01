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

interface BbcodeInterface
{
    /**
     * Обработка тэгов и ссылок
     */
    public function tags(string $string) : string;

    /**
     * Удаление BBcode тэгов
     */
    public function noTags(string $string) : string;

    /**
     * Панель кнопок для форматирования текстов в полях ввода
     */
    public function buttons(string $form, string $field) : string;
}
