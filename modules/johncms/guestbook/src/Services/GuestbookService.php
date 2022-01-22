<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Guestbook\Services;

use Exception;
use Johncms\Exceptions\ValidationException;
use Johncms\Files\FileStorage;
use Johncms\Guestbook\Models\Guestbook;
use Johncms\Guestbook\Resources\PostResource;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Settings\SiteSettings;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Johncms\Validator\Validator;
use League\Flysystem\FilesystemException;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

class GuestbookService
{
    protected ?User $user;
    protected array $config;
    protected SiteSettings $siteSettings;
    protected Session $session;

    public function __construct()
    {
        $this->user = di(User::class);
        $this->config = di('config')['johncms'];
        $this->siteSettings = di(SiteSettings::class);
        $this->session = di(Session::class);
    }

    /**
     * Retrieves a list of entries in the guestbook.
     *
     * @return array
     */
    public function getPosts(): array
    {
        $admin_club = ($this->session->has('ga') && $this->user?->hasPermission('guestbook_admin_club'));
        $messages = (new Guestbook())
            ->with('user', 'user.avatar', 'user.activity')
            ->where('adm', $admin_club)
            ->orderByDesc('time')
            ->paginate($this->siteSettings->getPerPage());

        $posts = PostResource::createFromCollection($messages);

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
        return ! $this->session->has('ga');
    }

    /**
     * Can we clear all the messages?
     *
     * @return bool
     */
    public function canClear(): bool
    {
        return (bool) $this->user?->hasPermission('guestbook_clear');
    }

    /**
     * Can we write to the guestbook?
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return ($this->user || $this->config['mod_guest'] === 2) && ! $this->user?->hasBan(['full', 'guestbook_write']);
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
            if ($this->canWrite() && ! $this->user) {
                $code = (new Code())->generate();
                $this->session->set('code', $code);
                return (new Image($code))->generate();
            }
        } catch (Exception) {
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
        $request = di(Request::class);
        $fields = $form->getFormData();
        $validation_rules = $form->getValidationRules();

        $validator = new Validator($fields, $validation_rules);
        if ($validator->isValid()) {
            $message = (new Guestbook())->create(
                [
                    'adm'            => ! $this->isGuestbook(),
                    'time'           => time(),
                    'user_id'        => $this->user?->id ?? 0,
                    'name'           => $this->user?->displayName() ?? $fields['name'],
                    'text'           => $fields['message'],
                    'ip'             => $request->getIp(),
                    'browser'        => $request->getUserAgent(),
                    'otvet'          => '',
                    'attached_files' => $fields['attached_files'],
                ]
            );
            if ($this->user) {
                // Update user activity
                $userManager = di(UserManager::class);
                $userManager->incrementActivity($this->user, 'guestbook_posts');
            }
        } else {
            throw ValidationException::withErrors($validator->getErrors());
        }

        $this->session->remove('code');

        return $message;
    }

    /**
     * Switching the mode of operation Guest / admin club
     */
    public function switchGuestbookType(): void
    {
        $request = di(Request::class);
        if ($this->user?->hasPermission('guestbook_admin_club')) {
            if ($request->getQuery('do', '') === 'set') {
                $this->session->set('ga', 1);
            } else {
                $this->session->remove('ga');
            }
        }
    }

    /**
     * Cleaning the guestbook
     *
     * @param int $period
     * @return string
     */
    public function clear(int $period = 0): string
    {
        $adm = $this->isGuestbook() ? 0 : 1;
        $storage = di(FileStorage::class);
        switch ($period) {
            case '1':
                // Clean messages older than 1 day
                $messages = (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 86400))->get();
                foreach ($messages as $message) {
                    if (! empty($message->attached_files)) {
                        foreach ($message->attached_files as $attached_file) {
                            try {
                                $storage->delete($attached_file);
                            } catch (Exception | FilesystemException) {
                            }
                        }
                    }
                }

                (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 86400))->delete();
                return __('All messages older than 1 day were deleted');

            case '2':
                // Perform a full cleanup
                $messages = (new Guestbook())->where('adm', $adm)->get();
                foreach ($messages as $message) {
                    if (! empty($message->attached_files)) {
                        foreach ($message->attached_files as $attached_file) {
                            try {
                                $storage->delete($attached_file);
                            } catch (Exception | FilesystemException) {
                            }
                        }
                    }
                }
                (new Guestbook())->where('adm', $adm)->delete();
                return __('Full clearing is finished');

            default:
                // Clean messages older than 1 week""
                $messages = (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 604800))->get();
                foreach ($messages as $message) {
                    if (! empty($message->attached_files)) {
                        foreach ($message->attached_files as $attached_file) {
                            try {
                                $storage->delete($attached_file);
                            } catch (Exception | FilesystemException) {
                            }
                        }
                    }
                }
                (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 604800))->delete();
                return __('All messages older than 1 week were deleted');
        }
    }
}
