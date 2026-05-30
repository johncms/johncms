<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Mail\Application\UseCases\GetOutgoingConversationsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class OutgoingConversationsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetOutgoingConversationsUseCase $getOutgoingConversationsUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->getOutgoingConversationsUseCase->execute($page, $perPage);

        $this->navChain->add(__('Sent messages'), '/mail/outgoing/');

        $pageTitle = __('Sent messages');
        $meta = new PageMeta($pageTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'mail::conversations',
            [
                'data' => [
                    'items' => $result->items->map(fn ($item) => $item->toArray())->all(),
                    'total' => $result->total,
                    'pagination' => $result->pagination,
                    'back_url' => $result->backUrl,
                ],
            ]
        );
    }
}
