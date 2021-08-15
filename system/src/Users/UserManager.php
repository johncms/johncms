<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Users\Exceptions\RuntimeException;
use Psr\Container\ContainerInterface;

class UserManager
{
    protected array $config;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $this->getConfig();
    }

    /**
     * Create user
     *
     * @param array $fields
     * @return User
     */
    public function create(array $fields): User
    {
        $this->checkFields($fields);
        $fields['password'] = password_hash($fields['password'], PASSWORD_DEFAULT);
        if (array_key_exists('email', $fields) && empty($fields['email'])) {
            $fields['email'] = null;
        }
        if (array_key_exists('login', $fields) && empty($fields['login'])) {
            $fields['login'] = null;
        }
        if (array_key_exists('phone', $fields) && empty($fields['phone'])) {
            $fields['phone'] = null;
        }

        return (new User())->create($fields);
    }

    /**
     * Update user
     *
     * @param int $user_id
     * @param array $fields
     * @return User
     */
    public function update(int $user_id, array $fields): User
    {
        if (array_key_exists('password', $fields)) {
            $fields['password'] = password_hash($fields['password'], PASSWORD_DEFAULT);
        }

        /** @var User $user */
        $user = (new User())->find($user_id);
        if ($user === null) {
            throw new RuntimeException(sprintf('The user with id %s was not found', $user_id));
        }
        $user->update($fields);
        return $user;
    }

    protected function checkFields(array $fields): void
    {
        $login_field = $this->config['login_field'];
        if (! array_key_exists($login_field, $fields) || empty($fields[$login_field])) {
            throw new RuntimeException(sprintf('The login field "%s" must be in the fields array', $login_field));
        }
        if (! array_key_exists('password', $fields) || empty($fields['password'])) {
            throw new RuntimeException('The password is not specified.');
        }
    }

    protected function getConfig(): array
    {
        $default = [
            'login_field' => 'email',
        ];
        $config = $this->container->get('config')['johncms']['users'] ?? [];
        return array_merge($default, $config);
    }
}
