<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Mail\Application\DTO\SendMessageCommand;
use Johncms\Modules\Mail\Application\Exceptions\SendMessageException;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\GetConversationUseCase;
use Johncms\Modules\Mail\Application\UseCases\SendMessageUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Psr\Http\Message\UploadedFileInterface;

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
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function send(int $id): string
    {
        $text = trim($this->editorContentNormalizer->trimEdgeEmptyBlocks((string) $this->request->getPost('text', '')));

        $files = $this->request->getUploadedFiles();
        $file = $files['fail'] ?? null;
        if (! $file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $file = null;
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
        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->getConversationUseCase->execute($id, $page, $this->currentUser->config->kmess);
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

        $meta = new PageMeta($conversationTitle, $page);
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
                    'total'           => $result->total,
                    'pagination'      => $result->pagination,
                    'clear_url'       => $result->clearUrl,
                    'back_url'        => $result->backUrl,
                ],
            ]
        );
    }
}
