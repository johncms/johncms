<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetActivityUseCase;
use Johncms\Modules\Profile\Domain\Enums\ActivityType;
use Johncms\NavChain;

final readonly class ActivityController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetActivityUseCase $getActivityUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function messages(int $id): ViewResponse
    {
        return $this->renderActivity($id, ActivityType::Messages);
    }

    public function topics(int $id): ViewResponse
    {
        return $this->renderActivity($id, ActivityType::Topics);
    }

    public function comments(int $id): ViewResponse
    {
        return $this->renderActivity($id, ActivityType::Comments);
    }

    private function renderActivity(int $id, ActivityType $type): ViewResponse
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
            return new ViewResponse(
                '@theme/pages/result.twig',
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
        return new ViewResponse(
            '@profile/public/activity.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'filters'     => $this->buildFilters($result->profileId, $type),
                'item_type'   => $result->itemType,
                'activity'    => $result->items,
                'total'       => $pagination->getTotal(),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
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
