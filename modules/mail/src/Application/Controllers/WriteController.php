<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\DTO\SendMessageCommand;
use Johncms\Modules\Mail\Application\Exceptions\SendMessageException;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\GetConversationUseCase;
use Johncms\Modules\Mail\Application\UseCases\SendMessageUseCase;
use Johncms\NavChain;
use Johncms\System\Utility\EditorContentNormalizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class WriteController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private EditorContentNormalizer $editorContentNormalizer,
        private GetConversationUseCase $getConversationUseCase,
        private SendMessageUseCase $sendMessageUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function send(Request $request, int $id): ViewResponse
    {
        $text = trim($this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('text', '')));

        $uploaded = $request->files->get('fail');
        $file = null;
        if ($uploaded instanceof UploadedFile && $uploaded->getError() !== UPLOAD_ERR_NO_FILE) {
            $file = $this->uploadedFileMapper->fromUploadedFile($uploaded);
        }

        try {
            $this->sendMessageUseCase->execute(new SendMessageCommand(
                recipientId: $id,
                text: $text,
                file: $file,
            ));
        } catch (SendMessageException $exception) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Mail'),
                    'type'          => 'alert-danger',
                    'message'       => $exception->getMessage(),
                    'back_url'      => '/mail/write/' . $id,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        redirect('/mail/write/' . $id);
    }

    public function conversation(int $id): ViewResponse
    {
        try {
            $pagination = $this->paginationFactory->create($this->getConversationUseCase->count($id));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->getConversationUseCase->getPage($id, $pagination->getPerPage(), $pagination->getOffset());
        } catch (UserNotFoundException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Mail'),
                    'type'     => 'alert-danger',
                    'message'  => __('User does not exists'),
                    'back_url' => '/mail/incoming',
                ]
            );
        }

        $conversationTitle = $result->nick !== '' ? $result->nick : __('New message');

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add($conversationTitle, '/mail/write/' . $id);

        $meta = new PageMeta($conversationTitle, $pagination->getCurrentPage());
        return new ViewResponse(
            '@mail/public/messages.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $conversationTitle,
                'description' => $meta->description,
                'ask_recipient' => $result->showNickInput,
                'can_see_meta'  => $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW),
                'data' => [
                    'errors'          => [],
                    'form_action'     => $result->formAction,
                    'nick'            => $result->nick,
                    'items'           => $result->items->map(fn ($item) => $item->toArray())->all(),
                    'total'           => $pagination->getTotal(),
                    'pagination'      => $pagination->render(),
                    'clear_url'       => $result->clearUrl,
                    'back_url'        => $result->backUrl,
                ],
            ]
        );
    }
}
