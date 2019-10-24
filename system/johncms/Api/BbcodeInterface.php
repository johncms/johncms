<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Api;

interface BbcodeInterface
{
    /**
     * Обработка тэгов и ссылок
     *
     * @param $string
     * @return mixed
     */
    public function tags($string);

    /**
     * Удаление BBcode тэгов
     *
     * @param $string
     * @return mixed
     */
    public function noTags($string);

    /**
     * Панель кнопок для форматирования текстов в полях ввода
     *
     * @param $form
     * @param $field
     * @return mixed
     */
    public function buttons($form, $field);
}
