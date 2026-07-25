<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\DTO\ChangePasswordCommand;
use Johncms\Modules\Profile\Application\DTO\ChangePasswordContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ChangePasswordException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\ChangePasswordUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetChangePasswordContextUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChangePasswordController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetChangePasswordContextUseCase $getChangePasswordContextUseCase,
        private ChangePasswordUseCase $changePasswordUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function form(int $id): Response
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

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return new Response(
            $this->render->render(
                'profile::password',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'form_action'             => '/profile/' . $context->profileUserId . '/password',
                        'show_old_password_field' => $context->isSelf,
                        'back_url'                => '/profile/' . $context->profileUserId,
                    ],
                ]
            )
        );
    }

    public function change(int $id): Response
    {
        try {
            $context = $this->getChangePasswordContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError(__('Change Password'), $e->getMessage(), 403);
        }

        $title = $this->buildTitle($context);
        $newPassword = trim($this->request->body('newpass', ''));

        try {
            $this->changePasswordUseCase->execute(new ChangePasswordCommand(
                profileUserId: $context->profileUserId,
                oldPassword: trim($this->request->body('oldpass', '')),
                newPassword: $newPassword,
                confirmPassword: trim($this->request->body('newconf', '')),
            ));
        } catch (ChangePasswordException $e) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => $e->getErrors(),
                        'back_url'      => '/profile/' . $context->profileUserId . '/password',
                        'back_url_name' => __('Repeat'),
                    ]
                )
            );
        }

        $response = new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Password successfully changed'),
                    'back_url'      => $context->isSelf ? '/login' : '/profile/' . $context->profileUserId,
                    'back_url_name' => __('Continue'),
                ]
            )
        );

        // Keep the persistent login cookie in sync after changing one's own password.
        // The legacy call omitted the path argument, so PHP sent no Path attribute and the
        // browser scoped the cookie to the "default path" per RFC 6265 5.1.4 (the current
        // request path with the last segment removed) rather than site-wide '/'. Cookie::create()
        // always forces an explicit Path attribute, so that default is reproduced here instead of
        // silently widening the cookie to '/'.
        if ($context->isSelf && isset($_COOKIE['cuid'], $_COOKIE['cups'])) {
            $response->headers->setCookie(
                Cookie::create('cups', md5($newPassword), time() + 3600 * 24 * 365, $this->defaultCookiePath(), null, false, false, false, null)
            );
        }

        return $response;
    }

    private function buildTitle(ChangePasswordContextDTO $context): string
    {
        return $context->profileUserName . ': ' . __('Change Password');
    }

    /**
     * RFC 6265 5.1.4 "default path" for the current request: the request path with everything
     * from (and including) the right-most slash removed, or '/' if there is none/only one slash.
     */
    private function defaultCookiePath(): string
    {
        $path = $this->request->getPathInfo();
        $lastSlash = strrpos($path, '/');

        if ($lastSlash === false || $lastSlash === 0) {
            return '/';
        }

        return substr($path, 0, $lastSlash);
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
