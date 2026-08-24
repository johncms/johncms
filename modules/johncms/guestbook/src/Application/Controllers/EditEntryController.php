<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\EditGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditEntryController
{
    public function __construct(
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private EditGuestbookEntryUseCase $editUseCase,
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
                    'title'    => __('Edit message'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $text = $this->editorContentNormalizer->trimEdgeEmptyBlocks(
            $request->body('message', (string) $entry->text)
        );
        $attachedFiles = (array) $request->bodyInts('attached_files');

        if ($request->getMethod() === 'POST') {
            $result = $this->validator->validate(
                ['message' => $text],
                ['message' => [new StringLength(min: 4, max: 16000)]]
            );
            if ($result->isValid()) {
                $this->editUseCase->execute($entry, $text, $attachedFiles);
                $this->session->flash('message', __('The message was saved'));
                redirect($baseUrl);
            }

            $errors = $result->getErrors();
        }

        return new ViewResponse(
            '@guestbook/public/edit.twig',
            [
                'title'      => __('Edit message'),
                'page_title' => __('Edit message'),
                'id'         => $id,
                'author'     => $entry->name,
                'text'       => $text,
                'errors'     => $errors,
            ]
        );
    }
}
