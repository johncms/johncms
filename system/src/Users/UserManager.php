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

use Carbon\Carbon;
use Johncms\Files\FileStorage;
use Johncms\Users\Exceptions\EmailIsNotConfirmedException;
use Johncms\Users\Exceptions\IncorrectPasswordException;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\Exceptions\UserIsNotConfirmedException;
use Johncms\Users\Exceptions\UserNotFoundException;
use League\Flysystem\FilesystemException;
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

        if (array_key_exists('birthday', $fields) && empty($fields['birthday'])) {
            $fields['birthday'] = null;
        } elseif (array_key_exists('birthday', $fields)) {
            $fields['birthday'] = Carbon::parse($fields['birthday']);
        }

        $user = (new User())->create($fields);
        if (array_key_exists('roles', $fields)) {
            $user->roles()->sync($fields['roles']);
        }

        return $user;
    }

    /**
     * Update user
     *
     * @param int $user_id
     * @param array $fields
     * @return User
     * @throws FilesystemException
     */
    public function update(int $user_id, array $fields): User
    {
        if (array_key_exists('password', $fields)) {
            $fields['password'] = password_hash($fields['password'], PASSWORD_DEFAULT);
        }

        if (array_key_exists('birthday', $fields) && empty($fields['birthday'])) {
            $fields['birthday'] = null;
        } elseif (array_key_exists('birthday', $fields)) {
            $fields['birthday'] = Carbon::parse($fields['birthday']);
        }

        /** @var User $user */
        $user = (new User())->find($user_id);
        if ($user === null) {
            throw new RuntimeException(sprintf('The user with id %s was not found', $user_id));
        }

        if (array_key_exists('additional_fields', $fields)) {
            $additionalFields = (array) $user->additional_fields;
            $fields['additional_fields'] = array_merge($additionalFields, $fields['additional_fields']);
        }

        if (array_key_exists('settings', $fields)) {
            $settings = (array) $user->settings;
            $fields['settings'] = array_merge($settings, $fields['settings']);
        }

        if (array_key_exists('avatar_id', $fields)) {
            $this->replaceAvatar($user, $fields);
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

    public function checkCredentials(string $username, string $password): User
    {
        $user = (new User())
            ->where('login', $username)
            ->orWhere('email', $username)
            ->orWhere('phone', $username)
            ->first();

        if (! $user) {
            throw new UserNotFoundException(__('The user "%s" was not found', $username));
        }

        if (! $user->confirmed) {
            throw new UserIsNotConfirmedException(__('The user is not confirmed'));
        }

        if (! $user->email_confirmed) {
            throw new EmailIsNotConfirmedException(__("The user's email is not verified"));
        }

        if (! password_verify($password, $user->password)) {
            $user->failed_login += 1;
            $user->save();
            throw new IncorrectPasswordException(__('Incorrect password'));
        }

        if ($user->failed_login) {
            $user->failed_login = null;
            $user->save();
        }

        return $user;
    }

    /**
     * @throws FilesystemException
     */
    protected function replaceAvatar(User $user, array $fields)
    {
        if (! empty($user->avatar_id) && $fields['avatar_id'] !== $user->avatar_id) {
            $fileStorage = di(FileStorage::class);
            $fileStorage->delete($user->avatar_id);
        }
    }

    /**
     * Update user activity
     *
     * @param User $user
     * @param array<string, mixed> $fields
     * @param bool $updateLastPostTime
     * @return bool
     */
    public function updateActivity(User $user, array $fields = [], bool $updateLastPostTime = true): bool
    {
        $activity = $user->activity;
        if (! $activity) {
            $activity = new UserActivity();
            $activity->user_id = $user->id;
        }

        if ($updateLastPostTime) {
            $activity->last_post = Carbon::now();
        }
        foreach ($fields as $key => $field) {
            $activity->$key = $field;
        }
        return $activity->save();
    }

    /**
     * Increase the value in a specific column of the user_activity table
     */
    public function incrementActivity(User $user, string $column): bool
    {
        $activity = $user->activity;
        if (! $activity) {
            $activity = new UserActivity();
            $activity->user_id = $user->id;
            $activity->$column = 0;
        }
        $activity->last_post = Carbon::now();
        $activity->$column += 1;
        return $activity->save();
    }
}
