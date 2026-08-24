<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetResetSettingsContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\ResetUserSettingsUseCase;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResetSettingsController
{
    public function __construct(
        private NavChain $navChain,
        private GetResetSettingsContextUseCase $getResetSettingsContextUseCase,
        private ResetUserSettingsUseCase $resetUserSettingsUseCase,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        $title = __('Reset user settings');

        try {
            $context = $this->getResetSettingsContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($title, $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($title, $e->getMessage(), 403);
        }

        $this->resetUserSettingsUseCase->execute($context->profileUserId);

        $this->navChain->add($context->profileUserName, '/profile/' . $context->profileUserId);
        $this->navChain->add($title);

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => $title,
                'type'     => 'alert-success',
                'message'  => sprintf(__('For user %s default settings were set.'), $context->profileUserName),
                'back_url' => '/profile/' . $context->profileUserId,
            ]
        );
    }

    private function renderError(string $title, string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $status
        );
    }
}
