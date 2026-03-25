<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Files\FileStorage;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DeleteEntryController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private Render $render,
        private Session $session,
        private FileStorage $storage,
        private LoggerInterface $logger,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $baseUrl = '/guestbook/';

        if ($this->request->getMethod() === 'POST') {
            $validator = new Validator(['csrf_token' => $this->request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
            if (! $validator->isValid()) {
                $this->session->flash('errors', $validator->getErrors());
                redirect($baseUrl);
            }
            // We clean the Guest, according to the specified parameters
            $id = $this->request->getPost('id', 0, FILTER_VALIDATE_INT);
            if (! $id) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'    => __('Delete message'),
                        'message'  => __('Wrong data'),
                        'type'     => 'alert-danger',
                        'back_url' => $baseUrl,
                    ]
                );
            }

            $post = (new GuestbookEntry())->find($id);
            if (! $post) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'    => __('Delete message'),
                        'message'  => __('Wrong data'),
                        'type'     => 'alert-danger',
                        'back_url' => $baseUrl,
                    ]
                );
            }

            if (! empty($post->attached_files)) {
                foreach ($post->attached_files as $attached_file) {
                    $fileId = filter_var($attached_file, FILTER_VALIDATE_INT);
                    if ($fileId === false) {
                        continue;
                    }

                    try {
                        $this->storage->delete((int) $fileId);
                    } catch (Throwable $exception) {
                        $this->logger->error($exception->getMessage(), [
                            'exception' => $exception,
                            'file'      => $attached_file,
                            'post_id'   => $post->id,
                        ]);
                    }
                }
            }
            $post->delete();
            // Set result message
            $this->session->flash('message', __('The message was deleted'));
        } else {
            return $this->render->render('guestbook::confirm_delete', ['id' => $this->request->getQuery('id')]);
        }

        redirect($baseUrl);
    }
}
