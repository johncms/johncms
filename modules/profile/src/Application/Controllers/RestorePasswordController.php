<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Application\UseCases\CompletePasswordRecoveryUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetRecoveryContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\SendPasswordRecoveryUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

final readonly class RestorePasswordController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private SendPasswordRecoveryUseCase $sendPasswordRecovery,
        private GetRecoveryContextUseCase $getRecoveryContext,
        private CompletePasswordRecoveryUseCase $completePasswordRecovery,
        private Session $session,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function form(): string
    {
        $this->navChain->add(__('Restore password'));

        $code = (string) new Code();
        $this->session->set('code', $code);

        return $this->render->render(
            'profile::restore_password',
            [
                'captcha' => new Image($code),
            ]
        );
    }

    public function send(): string
    {
        $this->navChain->add(__('Restore password'));

        $captchaValid = (new Validator(
            ['captcha' => $this->request->body('code')],
            ['captcha' => ['Captcha']]
        ))->isValid();
        $this->session->remove('code');

        if (! $captchaValid) {
            return $this->error(__('Incorrect code'));
        }

        try {
            $this->sendPasswordRecovery->execute(
                new SendRecoveryCommand(
                    nick: $this->request->body('nick', ''),
                    email: $this->request->body('email', ''),
                ),
                config('johncms')['homeurl'] ?? ''
            );
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Restore password'),
                'type'    => 'alert-success',
                'message' => __('Check your e-mail for further information'),
            ]
        );
    }

    public function setForm(int $id, string $code): string
    {
        $this->navChain->add(__('Restore password'));

        try {
            $this->getRecoveryContext->execute($id, $code);
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return $this->render->render(
            'profile::restore_password_set',
            [
                'form_action' => '/profile/password-recovery/set/' . $id . '/' . $code,
            ]
        );
    }

    public function set(int $id, string $code): string
    {
        $this->navChain->add(__('Restore password'));

        try {
            $user = $this->getRecoveryContext->execute($id, $code);
            $this->completePasswordRecovery->execute($user);
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Restore password'),
                'type'          => 'alert-success',
                'message'       => __('Password successfully changed.<br>New password sent to your E-mail address.'),
                'back_url'      => '/login',
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function error(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Restore password'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => '/profile/password-recovery',
                'back_url_name' => __('Back'),
            ]
        );
    }
}
