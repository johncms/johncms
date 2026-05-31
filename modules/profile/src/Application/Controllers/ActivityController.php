<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetActivityUseCase;
use Johncms\Modules\Profile\Domain\Enums\ActivityType;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ActivityController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetActivityUseCase $getActivityUseCase,
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
        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->getActivityUseCase->execute($id, $type, $this->currentUser->config->kmess);
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

        $meta = new PageMeta($pageTitle, $page);
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
                    'total'      => $result->total,
                    'pagination' => $result->pagination,
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
