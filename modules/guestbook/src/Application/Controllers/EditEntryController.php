<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\EditGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditEntryController
{
    public function __construct(
        private ControllerContext $context,
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private EditGuestbookEntryUseCase $editUseCase,
    ) {
        $this->context->initModule('guestbook');
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
                    'csrf_token' => $request->body('csrf_token', ''),
                ],
                $rules
            );
            if ($validator->isValid()) {
                $this->editUseCase->execute($entry, $text, $attachedFiles);
                $this->session->flash('message', __('The message was saved'));
                redirect($baseUrl);
            }

            $errors = $validator->getErrors();
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
