<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Services;

use Exception;
use Guestbook\Models\Guestbook;
use Guestbook\Resources\PostResource;
use Guestbook\Resources\ResourceCollection;
use Johncms\Exceptions\ValidationException;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

class GuestbookService
{
    /** @var User */
    protected $user;

    /** @var array */
    protected $config;

    /** @var array */
    protected $guest_access = [];

    public function __construct()
    {
        $this->user = di(User::class);
        $this->config = di('config')['johncms'];

        // Here you can (separated by commas) add the ID of those users who are not in the administration.
        // But who are allowed to read and write in the admin club
        $this->guest_access = [];
    }

    /**
     * Retrieves a list of entries in the guestbook.
     *
     * @return array
     */
    public function getPosts(): array
    {
        $admin_club = (isset($_SESSION['ga']) && ($this->user->rights >= 1 || in_array($this->user->id, $this->guest_access)));
        $messages = (new Guestbook())
            ->with('user')
            ->where('adm', $admin_club)
            ->orderByDesc('time')
            ->paginate($this->user->config->kmess);

        $posts = new ResourceCollection($messages, PostResource::class);

        return [
            'posts'      => $posts->toArray(),
            'pagination' => $messages->render(),
        ];
    }

    /**
     * Determine what we need to use? guestbook or admin-club?
     *
     * @return bool
     */
    public function isGuestbook(): bool
    {
        return ! isset($_SESSION['ga']);
    }

    /**
     * Can we clear all the messages?
     *
     * @return bool
     */
    public function canClear(): bool
    {
        return $this->user->rights >= 7;
    }

    /**
     * Can we write to the guestbook?
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return ($this->user->isValid() || $this->config['mod_guest'] === 2) && ! isset($this->user->ban['1']) && ! isset($this->user->ban['13']);
    }

    /**
     * Is the guestbook closed?
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return ! $this->config['mod_guest'];
    }

    /**
     * Security code
     *
     * @return string
     */
    public function getCaptcha(): string
    {
        try {
            if ($this->canWrite() && ! $this->user->isValid()) {
                $code = (new Code())->generate();
                $_SESSION['code'] = $code;
                return (new Image($code))->generate();
            }
        } catch (Exception $exception) {
        }

        return '';
    }

    /**
     * Adding a post to the guestbook.
     *
     * @return Guestbook
     */
    public function create(): Guestbook
    {
        $form = di(GuestbookForm::class);
        $env = di(Environment::class);
        $fields = $form->getFormData();
        $validation_rules = $form->getValidationRules();

        $validator = new Validator($fields, $validation_rules);
        if ($validator->isValid()) {
            $message = (new Guestbook())->create(
                [
                    'adm'     => ! $this->isGuestbook(),
                    'time'    => time(),
                    'user_id' => $this->user->id ?? 0,
                    'name'    => $this->user->isValid() ? $this->user->name : $fields['name'],
                    'text'    => $fields['message'],
                    'ip'      => $env->getIp(false),
                    'browser' => $env->getUserAgent(),
                    'otvet'   => '',
                ]
            );
            if ($this->user->isValid()) {
                $post_guest = $this->user->postguest + 1;
                (new User())
                    ->where('id', $this->user->id)
                    ->update(
                        [
                            'postguest' => $post_guest,
                            'lastpost'  => time(),
                        ]
                    );
            }
        } else {
            throw ValidationException::withErrors($validator->getErrors());
        }

        unset($_SESSION['code']);

        return $message;
    }

    /**
     * Switching the mode of operation Guest / admin club
     */
    public function switchGuestbookType(): void
    {
        $request = di(Request::class);
        if ($this->user->rights >= 1 || in_array($this->user->id, $this->guest_access)) {
            if ($request->getQuery('do', '') === 'set') {
                $_SESSION['ga'] = 1;
            } else {
                unset($_SESSION['ga']);
            }
        }
    }
}
