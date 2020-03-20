<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Users;

class UserConfig
{
    /** @var int Разрешить прямые Внешние ссылки */
    public $directUrl = 0;

    /** @var int Высота текстового поля ввода */
    public $fieldHeight = 3;

    /** @var int Ширина текстового поля ввода */
    public $fieldWidth = 40;

    /** @var int Размер списков */
    public $kmess = 10;

    /** @var string Выбранный пользователем язык */
    public $lng = '';

    /** @var string Тема оформления */
    public $skin = '';

    /** @var int Временной сдвиг */
    public $timeshift = 0;

    /** @var int Показать Youtube player */
    public $youtube = 1;

    public function __construct(string $serializedArray = '')
    {
        if ($serializedArray !== '') {
            $this->assignValues(unserialize($serializedArray, ['allowed_classes' => false]));
        }
    }

    /**
     * @param mixed $data
     */
    private function assignValues($data): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
