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

namespace Johncms;

use Zend\Stdlib\ArrayObject;

/**
 * Class UserConfig
 *
 * @package Johncms
 *
 * @property $directUrl
 * @property $fieldHeight
 * @property $fieldWidth
 * @property $kmess
 * @property $skin
 * @property $timeshift
 * @property $youtube
 */
class UserConfig extends ArrayObject
{
    public function __construct(User $user)
    {
        $input = empty($user->set_user) ? $this->getDefaults() : unserialize($user->set_user);
        parent::__construct($input, parent::ARRAY_AS_PROPS);
    }

    private function getDefaults()
    {
        return [
            'directUrl'   => 0,  // Внешние ссылки
            'fieldHeight' => 3,  // Высота текстового поля ввода
            'fieldWidth'  => 40, // Ширина текстового поля ввода
            'kmess'       => 20, // Число сообщений на страницу
            'skin'        => '', // Тема оформления
            'timeshift'   => 0,  // Временной сдвиг
            'youtube'     => 1,  // Покалывать ли Youtube player
        ];
    }
}
