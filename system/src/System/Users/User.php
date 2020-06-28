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

class User extends AbstractUserProperties
{
    public function __construct(array $properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }

        $this->config = new UserConfig($this->set_user);
    }

    public function isValid(): bool
    {
        $config = di('config')['johncms'];
        return ($this->id > 0 && $this->preg == 1 && (empty($config['user_email_confirmation']) || $this->email_confirmed == 1));
    }
}
