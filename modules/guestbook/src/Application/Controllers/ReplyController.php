<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\ReplyToGuestbookEntryUseCase;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class ReplyController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private Render $render,
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private GuestbookEntryTextFormatter $textFormatter,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private ReplyToGuestbookEntryUseCase $replyUseCase,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): Response
    {
        $baseUrl = '/guestbook/';

        $id = $this->request->queryInt('id');
        $errors = [];
        $this->render->addData(['title' => __('Reply'), 'page_title' => __('Reply')]);

        try {
            $entry = $this->contextUseCase->execute($id);
            $this->manageAccessUseCase->execute($entry);
        } catch (GuestbookEntryNotFoundException) {
            pageNotFound();
        } catch (GuestbookAccessDeniedException) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'    => __('Reply'),
                        'message'  => __('Wrong data'),
                        'type'     => 'alert-danger',
                        'back_url' => $baseUrl,
                    ]
                ),
                Response::HTTP_FORBIDDEN
            );
        }

        $text = $this->editorContentNormalizer->trimEdgeEmptyBlocks(
            $this->request->body('message', (string) $entry->otvet)
        );
        $attachedFiles = (array) $this->request->bodyInts('attached_files');

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

            $validator = new Validator(
                [
                    'message'    => $text,
                    'csrf_token' => $this->request->body('csrf_token', ''),
                ],
                $rules
            );
            if ($validator->isValid()) {
                $this->replyUseCase->execute($entry, $text, $attachedFiles);
                $this->session->flash('message', __('Your reply to the message was saved'));
                redirect($baseUrl);
            }

            $errors = $validator->getErrors();
        }

        return new Response(
            $this->render->render(
                'guestbook::reply',
                [
                    'id'       => $id,
                    'message'  => $entry,
                    'postText' => $this->textFormatter->formatPost($entry),
                    'text'     => $text,
                    'errors'   => $errors,
                ]
            )
        );
    }
}
