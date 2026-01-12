<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class ReplyController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private Render $render,
        private Session $session,
        private User $user,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $baseUrl = '/guestbook/';

        $id = $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $errors = [];
        $this->render->addData(['title' => __('Reply'), 'page_title' => __('Reply')]);

        try {
            $message = (new GuestbookEntry())->findOrFail($id);
        } catch (ModelNotFoundException) {
            pageNotFound();
        }

        $form_data = [
            'message'        => $this->request->getPost('message', $message->otvet),
            'csrf_token'     => $this->request->getPost('csrf_token', ''),
            'attached_files' => (array) $this->request->getPost('attached_files', [], FILTER_VALIDATE_INT),
        ];

        if ($this->request->getMethod() === 'POST') {
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
                        'admin'          => $this->user->name,
                        'otime'          => time(),
                        'attached_files' => array_merge((array) $message->attached_files, $form_data['attached_files']),
                    ]
                );
                $this->session->flash('message', __('Your reply to the message was saved'));
                redirect($baseUrl);
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
}
