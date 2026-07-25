<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetIpHistoryUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class IpHistoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetIpHistoryUseCase $getIpHistoryUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): Response
    {
        $title = __('IP History');

        try {
            $pagination = $this->paginationFactory->create($this->getIpHistoryUseCase->count($id));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->getIpHistoryUseCase->getPage($id, $pagination->getPerPage(), $pagination->getOffset());
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($title, $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($title, $e->getMessage(), 403);
        }

        $this->navChain->add($result->profileName, $result->backUrl);
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);

        return new Response(
            $this->render->render(
                'profile::ip_history',
                [
                    'data' => [
                        'items'      => $result->items,
                        'total'      => $pagination->getTotal(),
                        'pagination' => $pagination->render(),
                        'back_url'   => $result->backUrl,
                    ],
                ]
            )
        );
    }

    private function renderError(string $title, string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => $message,
                ]
            ),
            $status
        );
    }
}
