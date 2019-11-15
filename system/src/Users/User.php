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

use Johncms\Api\UserInterface;
use Zend\Stdlib\ArrayObject;

class User extends ArrayObject implements UserInterface
{
    public function __construct(array $input)
    {
        parent::__construct($input, parent::ARRAY_AS_PROPS);
        $this->storage['config'] = new UserConfig($this);
    }

    /**
     * @inheritDoc
     */
    public function isValid() : bool
    {
        return $this->offsetGet('id') > 0 && $this->offsetGet('preg') == 1
            ? true
            : false;
    }

    public function offsetSet($key, $value)
    {
        throw new \LogicException('User object are immutable');
    }

    /**
     * Метод возвращает путь к аватару пользователя
     *
     * @return string
     */
    public function getAvatar() : string
    {
        if ($this->isValid()) {
            $avatar = 'users/avatar/' . $this->id . '.png';
            if (file_exists(UPLOAD_PATH . $avatar)) {
                return UPLOAD_PUBLIC_PATH . $avatar;
            }
        }

        return '';
    }
}
