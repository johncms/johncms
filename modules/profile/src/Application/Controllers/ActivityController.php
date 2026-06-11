<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetActivityUseCase;
use Johncms\Modules\Profile\Domain\Enums\ActivityType;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ActivityController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetActivityUseCase $getActivityUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function messages(int $id): string
    {
        return $this->renderActivity($id, ActivityType::Messages);
    }

    public function topics(int $id): string
    {
        return $this->renderActivity($id, ActivityType::Topics);
    }

    public function comments(int $id): string
    {
        return $this->renderActivity($id, ActivityType::Comments);
    }

    private function renderActivity(int $id, ActivityType $type): string
    {
        try {
            $pagination = $this->paginationFactory->create($this->getActivityUseCase->count($id, $type));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->getActivityUseCase->getPage(
                $id,
                $type,
                $pagination->getPerPage(),
                $pagination->getOffset()
            );
        } catch (ProfileNotFoundException $e) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Activity'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $pageTitle = __('Activity') . ' - ' . $result->profileName;

        $this->navChain->add($result->profileName, '/profile/' . $result->profileId);
        $this->navChain->add(__('Activity'));

        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'profile::activity',
            [
                'data' => [
                    'filters'    => $this->buildFilters($result->profileId, $type),
                    'item_type'  => $result->itemType,
                    'activity'   => $result->items,
                    'total'      => $pagination->getTotal(),
                    'pagination' => $pagination->render(),
                ],
            ]
        );
    }

    /**
     * @return array<string, array{name: string, url: string, active: bool}>
     */
    private function buildFilters(int $profileId, ActivityType $active): array
    {
        $base = '/profile/' . $profileId . '/activity';

        return [
            'messages' => [
                'name'   => __('Messages'),
                'url'    => $base,
                'active' => $active === ActivityType::Messages,
            ],
            'topic' => [
                'name'   => __('Themes'),
                'url'    => $base . '/topics',
                'active' => $active === ActivityType::Topics,
            ],
            'comments' => [
                'name'   => __('Comments'),
                'url'    => $base . '/comments',
                'active' => $active === ActivityType::Comments,
            ],
        ];
    }
}
