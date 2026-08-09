<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChangePasswordController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetChangePasswordContextUseCase $getChangePasswordContextUseCase,
        private ChangePasswordUseCase $changePasswordUseCase,
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

        // Keep the persistent login cookie in sync after changing one's own password.
        // The legacy call omitted the path argument, so PHP sent no Path attribute and the
        // browser scoped the cookie to the "default path" per RFC 6265 5.1.4 (the current
        // request path with the last segment removed) rather than site-wide '/'. Cookie::create()
        // always forces an explicit Path attribute, so that default is reproduced here instead of
        // silently widening the cookie to '/'.
        $cookies = [];
        if ($context->isSelf && $request->cookies->has('cuid') && $request->cookies->has('cups')) {
            $cookies[] = Cookie::create(
                'cups',
                md5($newPassword),
                time() + 3600 * 24 * 365,
                $this->defaultCookiePath($request),
                null,
                false,
                false,
                false,
                null
            );
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Password successfully changed'),
                'back_url'      => $context->isSelf ? '/login' : '/profile/' . $context->profileUserId,
                'back_url_name' => __('Continue'),
            ],
            cookies: $cookies
        );
    }

    private function buildTitle(ChangePasswordContextDTO $context): string
    {
        return $context->profileUserName . ': ' . __('Change Password');
    }

    /**
     * RFC 6265 5.1.4 "default path" for the current request: the request path with everything
     * from (and including) the right-most slash removed, or '/' if there is none/only one slash.
     */
    private function defaultCookiePath(Request $request): string
    {
        $path = $request->getPathInfo();
        $lastSlash = strrpos($path, '/');

        if ($lastSlash === false || $lastSlash === 0) {
            return '/';
        }

        return substr($path, 0, $lastSlash);
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
