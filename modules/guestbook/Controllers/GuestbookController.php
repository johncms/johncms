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

use Exception;
use Guestbook\Models\Guestbook;
use Guestbook\Services\GuestbookForm;
use Guestbook\Services\GuestbookService;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use League\Flysystem\FilesystemException;

class GuestbookController extends BaseController
{
    protected $module_name = 'guestbook';

    /** @var string */
    protected $page_title = '';

    /** @var string */
    protected $base_url = '/guestbook/';

    public function __construct()
    {
        parent::__construct();
        $guestbook = di(GuestbookService::class);
        $this->page_title = $guestbook->isGuestbook() ? __('Guestbook') : __('Admin Club');
        $this->nav_chain->add($this->page_title, $this->base_url);

        $config = di('config')['johncms'];
        $user = di(User::class);
        // If the guest is closed, display a message and close access (except for Admins)
        if (! $config['mod_guest'] && $user->rights < 7) {
            echo $this->render->render(
                'system::pages/result',
                [
                    'title'    => $this->page_title,
                    'message'  => __('Guestbook is closed'),
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
            exit;
        }
    }

    public function index(GuestbookService $guestbook, GuestbookForm $form, Request $request, Session $session): string
    {
        $this->render->addData(['title' => $this->page_title, 'page_title' => $this->page_title]);

        $flash_errors = $session->getFlash('errors');
        $errors = $flash_errors ?? [];

        // If the form was sent using POST method, then try to create the new post.
        if ($request->getMethod() === 'POST' && $guestbook->canWrite()) {
            try {
                $guestbook->create();
                $session->flash('message', __('Your message was added successfully'));
                redirect($this->base_url);
            } catch (ValidationException $exception) {
                $errors = $exception->getErrors();
            }
        }

        $posts = $guestbook->getPosts();

        return $this->render->render(
            'guestbook::index',
            [
                'data' => [
                    'message'      => $session->getFlash('message'),
                    'errors'       => $errors,
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
        redirect($this->base_url);
    }

    /**
     * Cleaning the guestbook
     *
     * @param Request $request
     * @param GuestbookService $guestbook
     * @param Session $session
     * @return string
     */
    public function clean(Request $request, GuestbookService $guestbook, Session $session): string
    {
        if ($request->getMethod() === 'POST') {
            $validator = new Validator(['csrf_token' => $request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
            if (! $validator->isValid()) {
                $session->flash('errors', $validator->getErrors());
                redirect($this->base_url);
            }
            // We clean the Guest, according to the specified parameters
            $period = $request->getPost('cl', 0, FILTER_VALIDATE_INT);
            $message = $guestbook->clear($period);
            // Set result message
            $session->flash('message', $message);
            redirect($this->base_url);
        }
        // Request cleaning options
        return $this->render->render('guestbook::clear');
    }

    /**
     * Cleaning the guestbook
     *
     * @param Request $request
     * @param Session $session
     * @param FileStorage $storage
     * @return string
     */
    public function delete(Request $request, Session $session, FileStorage $storage): string
    {
        if ($request->getMethod() === 'POST') {
            $validator = new Validator(['csrf_token' => $request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
            if (! $validator->isValid()) {
                $session->flash('errors', $validator->getErrors());
                redirect($this->base_url);
            }
            // We clean the Guest, according to the specified parameters
            $id = $request->getPost('id', 0, FILTER_VALIDATE_INT);
            $post = (new Guestbook())->find($id);
            if (! empty($post->attached_files)) {
                foreach ($post->attached_files as $attached_file) {
                    try {
                        $storage->delete($attached_file);
                    } catch (Exception | FilesystemException $exception) {
                    }
                }
            }
            $post->delete();
            // Set result message
            $session->flash('message', __('The message was deleted'));
        } else {
            return $this->render->render('guestbook::confirm_delete', ['id' => $request->getQuery('id')]);
        }

        redirect($this->base_url);
    }

    /**
     * The edit message page
     *
     * @param User $user
     * @param Request $request
     * @param Session $session
     * @return string
     */
    public function edit(User $user, Request $request, Session $session): string
    {
        $id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $errors = [];
        $this->render->addData(['title' => __('Edit message'), 'page_title' => __('Edit message')]);

        try {
            $message = (new Guestbook())->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        $form_data = [
            'message'        => $request->getPost('message', $message->text),
            'csrf_token'     => $request->getPost('csrf_token', ''),
            'attached_files' => (array) $request->getPost('attached_files', [], FILTER_VALIDATE_INT),
        ];
        if ($request->getMethod() === 'POST') {
            $rules = [
                'message'    => [
                    'NotEmpty',
                    'StringLength' => ['min' => 4, 'max' => 16000],
                ],
                'csrf_token' => [
                    'Csrf',
                ],
            ];

            $validator = new Validator($form_data, $rules);
            if ($validator->isValid()) {
                $message->update(
                    [
                        'text'           => $form_data['message'],
                        'edit_who'       => $user->name,
                        'edit_time'      => time(),
                        'edit_count'     => ($message->edit_count + 1),
                        'attached_files' => array_merge((array) $message->attached_files, $form_data['attached_files']),
                    ]
                );
                $session->flash('message', __('The message was saved'));
                redirect($this->base_url);
            }

            $errors = $validator->getErrors();
        }

        return $this->render->render(
            'guestbook::edit',
            [
                'id'      => $id,
                'message' => $message,
                'text'    => htmlspecialchars($form_data['message']),
                'errors'  => $errors,
            ]
        );
    }

    /**
     * The reply to message page
     *
     * @param User $user
     * @param Request $request
     * @param Session $session
     * @return string
     */
    public function reply(User $user, Request $request, Session $session): string
    {
        $id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $errors = [];
        $this->render->addData(['title' => __('Reply'), 'page_title' => __('Reply')]);

        try {
            $message = (new Guestbook())->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        $form_data = [
            'message'        => $request->getPost('message', $message->otvet),
            'csrf_token'     => $request->getPost('csrf_token', ''),
            'attached_files' => (array) $request->getPost('attached_files', [], FILTER_VALIDATE_INT),
        ];

        if ($request->getMethod() === 'POST') {
            $rules = [
                'message'    => [
                    'NotEmpty',
                    'StringLength' => ['min' => 4, 'max' => 16000],
                ],
                'csrf_token' => [
                    'Csrf',
                ],
            ];

            $validator = new Validator($form_data, $rules);
            if ($validator->isValid()) {
                $message->update(
                    [
                        'otvet'          => $form_data['message'],
                        'admin'          => $user->name,
                        'otime'          => time(),
                        'attached_files' => array_merge((array) $message->attached_files, $form_data['attached_files']),
                    ]
                );
                $session->flash('message', __('Your reply to the message was saved'));
                redirect($this->base_url);
            }

            $errors = $validator->getErrors();
        }

        return $this->render->render(
            'guestbook::reply',
            [
                'id'         => $id,
                'message'    => $message,
                'errors'     => $errors,
                'reply_text' => htmlspecialchars($message->reply_text),
            ]
        );
    }

    public function loadFile(Request $request): string
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->getUploadedFiles();
            $file_info = new FileInfo($files['upload']->getClientFilename());
            if (! $file_info->isImage()) {
                return json_encode(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ]
                );
            }

            $file = (new FileStorage())->saveFromRequest('upload', 'guestbook');
            $file_array = [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
            header('Content-Type: application/json');
            return json_encode($file_array);
        } catch (FilesystemException | Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            return json_encode(['errors' => $e->getMessage()]);
        }
    }
}
