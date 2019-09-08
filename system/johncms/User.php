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
 * Class User
 *
 * @package Johncms
 */
class User extends ArrayObject implements Api\UserInterface
{
    private $userConfigObject;

    /**
     * User constructor.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input, parent::ARRAY_AS_PROPS);
    }

    /**
     * User validation
     *
     * @return bool
     */
    public function isValid()
    {
        if ($this->offsetGet('id') > 0
            && $this->offsetGet('preg') == 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get User config
     *
     * @return UserConfig
     */
    public function getConfig()
    {
        if (null === $this->userConfigObject) {
            $this->userConfigObject = new UserConfig($this);
        }

        return $this->userConfigObject;
    }
}
