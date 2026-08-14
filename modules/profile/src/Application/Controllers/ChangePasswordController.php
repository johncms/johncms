<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SignInManager;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\DTO\ChangePasswordCommand;
use Johncms\Modules\Profile\Application\DTO\ChangePasswordContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ChangePasswordException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\ChangePasswordUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetChangePasswordContextUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;

final readonly class ChangePasswordController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetChangePasswordContextUseCase $getChangePasswordContextUseCase,
        private ChangePasswordUseCase $changePasswordUseCase,
        private SignInManager $signInManager,
    ) {
    }

    public function form(int $id): ViewResponse
    {
        try {
            $context = $this->getChangePasswordContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage(), 403);
        }

        $title = $this->buildTitle($context);
        $this->navChain->add($context->profileUserName, '/profile/' . $context->profileUserId);
        $this->navChain->add(__('Change Password'));

        return new ViewResponse(
            '@profile/public/password.twig',
            [
                'title'                   => $title,
                'page_title'              => $title,
                'form_action'             => '/profile/' . $context->profileUserId . '/password',
                'show_old_password_field' => $context->isSelf,
                'back_url'                => '/profile/' . $context->profileUserId,
            ]
        );
    }

    public function change(Request $request, int $id): ViewResponse
    {
        try {
            $context = $this->getChangePasswordContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage(), 403);
        }

        $title = $this->buildTitle($context);
        $newPassword = trim($request->body('newpass', ''));

        try {
            $this->changePasswordUseCase->execute(new ChangePasswordCommand(
                profileUserId: $context->profileUserId,
                oldPassword: trim($request->body('oldpass', '')),
                newPassword: $newPassword,
                confirmPassword: trim($request->body('newconf', '')),
            ));
        } catch (ChangePasswordException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => $title,
                    'type'          => 'alert-danger',
                    'message'       => $e->getErrors(),
                    'back_url'      => '/profile/' . $context->profileUserId . '/password',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        // A changed password closes every other session: whoever knew the old one — including
        // somebody holding a copy of the cookie — is signed out everywhere but here.
        $this->signInManager->signOutEverywhereElse(
            $context->profileUserId,
            SessionRevocationReason::PasswordChange
        );

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Password successfully changed'),
                'back_url'      => $context->isSelf ? '/login' : '/profile/' . $context->profileUserId,
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function buildTitle(ChangePasswordContextDTO $context): string
    {
        return $context->profileUserName . ': ' . __('Change Password');
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
