<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Mail\Application\UseCases\GetAttachedFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class FilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetAttachedFilesUseCase $getAttachedFilesUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));

        $result = $this->getAttachedFilesUseCase->execute($this->currentUser->config->kmess);

        $this->navChain->add(__('My Account'), '/profile/?act=office');
        $this->navChain->add(__('Mail'), '/mail/');
        $this->navChain->add(__('Files'), '/mail/files/');

        $pageTitle = __('Files');
        $meta = new PageMeta($pageTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'mail::files',
            [
                'data' => [
                    'items'      => $result->items->map(fn ($item) => $item->toArray())->all(),
                    'total'      => $result->total,
                    'pagination' => $result->pagination,
                    'back_url'   => $result->backUrl,
                ],
            ]
        );
    }
}
