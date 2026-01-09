<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Modules\Guestbook\Application\Services\GuestbookService;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
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

    public function index(
        ListGuestbookEntriesUseCase $guestbookEntries,
        GuestbookAccess $access,
        GuestbookService $guestbook,
        GuestbookForm $form,
        Request $request,
        Session $session
    ): string {
        $this->render->addData(['title' => $this->page_title, 'page_title' => $this->page_title]);

        $flash_errors = $session->getFlash('errors');
        $errors = $flash_errors ?? [];

        // If the form was sent using POST method, then try to create the new post.
        if ($request->getMethod() === 'POST' && $access->canWrite()) {
            try {
                $guestbook->create();
                $session->flash('message', __('Your message was added successfully'));
                redirect($this->base_url);
            } catch (ValidationException $exception) {
                $errors = $exception->getErrors();
            }
        }

        $posts = $guestbookEntries->execute();

        return $this->render->render(
            'guestbook::index',
            [
                'posts'      => $posts['posts'],
                'pagination' => $posts['pagination'],
                'isClosed'   => $access->isClosed(),
                'canWrite'   => $access->canWrite(),
                'canClear'   => $access->canClear(),
                'errors'     => $errors,
                'formData'   => $form->getFormData(),
                'captcha'    => $guestbook->getCaptcha(),
                'message'    => $session->getFlash('message'),
            ]
        );
    }

    /**
     * Switching the mode of operation Guest / admin club
     */
    public function switchGuestbookType(GuestbookMode $guestbookMode, Request $request): void
    {
        $guestbookMode->switch($request);
        redirect($this->base_url);
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
