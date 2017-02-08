<?php

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
