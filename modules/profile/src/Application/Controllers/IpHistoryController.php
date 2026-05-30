<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetIpHistoryUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class IpHistoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetIpHistoryUseCase $getIpHistoryUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): string
    {
        $title = __('IP History');
        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->getIpHistoryUseCase->execute($id, $this->currentUser->config->kmess);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($title, $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($title, $e->getMessage());
        }

        $this->navChain->add($result->profileName, $result->backUrl);
        $this->navChain->add($title);

        $meta = new PageMeta($title, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'profile::ip_history',
            [
                'data' => [
                    'items'      => $result->items,
                    'total'      => $result->total,
                    'pagination' => $result->pagination,
                    'back_url'   => $result->backUrl,
                ],
            ]
        );
    }

    private function renderError(string $title, string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }
}
