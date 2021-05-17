<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Controllers;

use Guestbook\Services\GuestbookForm;
use Guestbook\Services\GuestbookService;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;

class GuestbookController extends BaseController
{
    protected $module_name = 'guestbook';

    public function __construct()
    {
        parent::__construct();
        $this->nav_chain->add(__('Guestbook'), '/guestbook/');
    }

    public function index(GuestbookService $guestbook, GuestbookForm $form, Request $request): string
    {
        $this->render->addData(['title' => __('Guestbook'), 'page_title' => __('Guestbook')]);

        // If the form was sent using POST method, then try to create the new post.
        if ($request->getMethod() === 'POST') {
            try {
                $guestbook->create();
                header('Location: /guestbook/');
                exit;
            } catch (ValidationException $exception) {
                $errors = $exception->getErrors();
            }
        }

        $posts = $guestbook->getPosts();

        return $this->render->render(
            'guestbook::index',
            [
                'data' => [
                    'errors'       => $errors ?? [],
                    'form_data'    => $form->getFormData(),
                    'is_closed'    => $guestbook->isClosed(),
                    'can_write'    => $guestbook->canWrite(),
                    'can_clear'    => $guestbook->canClear(),
                    'is_guestbook' => $guestbook->isGuestbook(),
                    'captcha'      => $guestbook->getCaptcha(),
                    'posts'        => $posts['posts'],
                    'pagination'   => $posts['pagination'],
                ],
            ]
        );
    }

    /**
     * Switching the mode of operation Guest / admin club
     *
     * @param GuestbookService $guestbook
     */
    public function switchGuestbookType(GuestbookService $guestbook): void
    {
        $guestbook->switchGuestbookType();
        header('Location: /guestbook/');
        exit;
    }

    /**
     * Cleaning the guestbook
     *
     * @param Request $request
     * @param User $user
     * @param GuestbookService $guestbook
     * @return string
     */
    public function clean(Request $request, User $user, GuestbookService $guestbook): string
    {
        if ($user->rights >= 7) {
            if ($request->getMethod() === 'POST') {
                $validator = new Validator(['csrf_token' => $request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
                if (! $validator->isValid()) {
                    header('Location: /guestbook/');
                    exit;
                }
                // We clean the Guest, according to the specified parameters
                $period = $request->getPost('cl', 0, FILTER_VALIDATE_INT);
                $message = $guestbook->clear($period);
                return $this->render->render(
                    'guestbook::result',
                    [
                        'title'    => __('Clear guestbook'),
                        'message'  => $message,
                        'type'     => 'success',
                        'back_url' => '/guestbook/',
                    ]
                );
            } else {
                // Request cleaning options
                return $this->render->render('guestbook::clear');
            }
        } else {
            header('Location: /guestbook/');
            exit;
        }
    }
}
