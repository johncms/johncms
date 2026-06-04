<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetResetSettingsContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\ResetUserSettingsUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ResetSettingsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetResetSettingsContextUseCase $getResetSettingsContextUseCase,
        private ResetUserSettingsUseCase $resetUserSettingsUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): string
    {
        $title = __('Reset user settings');

        try {
            $context = $this->getResetSettingsContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($title, $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($title, $e->getMessage());
        }

        $this->resetUserSettingsUseCase->execute($context->profileUserId);

        $this->navChain->add($context->profileUserName, '/profile/' . $context->profileUserId);
        $this->navChain->add($title);

        return $this->render->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-success',
                'message'  => sprintf(__('For user %s default settings were set.'), $context->profileUserName),
                'back_url' => '/profile/' . $context->profileUserId,
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
