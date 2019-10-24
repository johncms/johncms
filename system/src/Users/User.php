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

/**
 * Class User
 *
 * @package Johncms
 */
class User extends ArrayObject implements UserInterface
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
