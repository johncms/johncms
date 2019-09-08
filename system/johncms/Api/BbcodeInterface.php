<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
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
