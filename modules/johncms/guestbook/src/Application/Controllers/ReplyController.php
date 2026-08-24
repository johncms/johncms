<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\ReplyToGuestbookEntryUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class ReplyController
{
    public function __construct(
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private GuestbookEntryTextFormatter $textFormatter,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private ReplyToGuestbookEntryUseCase $replyUseCase,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $baseUrl = '/guestbook/';

        $id = $request->queryInt('id');
        $errors = [];
        try {
            $entry = $this->contextUseCase->execute($id);
            $this->manageAccessUseCase->execute($entry);
        } catch (GuestbookEntryNotFoundException) {
            pageNotFound();
        } catch (GuestbookAccessDeniedException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Reply'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $text = $this->editorContentNormalizer->trimEdgeEmptyBlocks(
            $request->body('message', (string) $entry->otvet)
        );
        $attachedFiles = (array) $request->bodyInts('attached_files');

        if ($request->getMethod() === 'POST') {
            $result = $this->validator->validate(
                ['message' => $text],
                ['message' => [new StringLength(min: 4, max: 16000)]]
            );
            if ($result->isValid()) {
                $this->replyUseCase->execute($entry, $text, $attachedFiles);
                $this->session->flash('message', __('Your reply to the message was saved'));
                redirect($baseUrl);
            }

            $errors = $result->getErrors();
        }

        return new ViewResponse(
            '@guestbook/public/reply.twig',
            [
                'title'      => __('Reply'),
                'page_title' => __('Reply'),
                'id'         => $id,
                'author'     => $entry->name,
                'post_text'  => $this->textFormatter->formatPost($entry),
                'text'       => $text,
                'errors'     => $errors,
            ]
        );
    }
}
