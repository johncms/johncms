<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Users;

use Johncms\Api\UserConfigInterface;
use Zend\Stdlib\ArrayObject;

class UserConfig extends ArrayObject implements UserConfigInterface
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
