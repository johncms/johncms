<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\UploadedFileMapper;
use Johncms\Modules\Mail\Application\DTO\SendMessageCommand;
use Johncms\Modules\Mail\Application\Exceptions\SendMessageException;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\GetConversationUseCase;
use Johncms\Modules\Mail\Application\UseCases\SendMessageUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class WriteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private EditorContentNormalizer $editorContentNormalizer,
        private GetConversationUseCase $getConversationUseCase,
        private SendMessageUseCase $sendMessageUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function send(int $id): string
    {
        $text = trim($this->editorContentNormalizer->trimEdgeEmptyBlocks($this->request->body('text', '')));

        $uploaded = $this->request->files->get('fail');
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
            return $this->render->render(
                'system::pages/result',
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

    public function conversation(int $id): string
    {
        try {
            $pagination = $this->paginationFactory->create($this->getConversationUseCase->count($id));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->getConversationUseCase->getPage($id, $pagination->getPerPage(), $pagination->getOffset());
        } catch (UserNotFoundException) {
            return $this->render->render(
                'system::pages/result',
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
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $conversationTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'mail::messages',
            [
                'data' => [
                    'errors'          => [],
                    'form_action'     => $result->formAction,
                    'show_nick_input' => $result->showNickInput,
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
