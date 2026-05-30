<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\GetConversationUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class WriteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetConversationUseCase $getConversationUseCase,
    ) {
        $this->controllerContext->initModule('mail');
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
                    'back_url' => '/mail/',
                ]
            );
        }

        $this->navChain->add(__('My Account'), '/profile/?act=office');
        $this->navChain->add(__('Mail'), '/mail/');

        $pageTitle = __('Mail');
        $meta = new PageMeta($pageTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
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
                    'bbcode'          => $result->bbcode,
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
