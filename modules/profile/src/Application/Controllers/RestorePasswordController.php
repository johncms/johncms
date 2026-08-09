<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Application\UseCases\CompletePasswordRecoveryUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetRecoveryContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\SendPasswordRecoveryUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Validator\Validator;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

final readonly class RestorePasswordController
{
    public function __construct(
        private NavChain $navChain,
        private SendPasswordRecoveryUseCase $sendPasswordRecovery,
        private GetRecoveryContextUseCase $getRecoveryContext,
        private CompletePasswordRecoveryUseCase $completePasswordRecovery,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        $code = (string) new Code();
        $this->session->set('code', $code);

        return new ViewResponse(
            '@profile/public/restore-password.twig',
            [
                'captcha' => new Image($code),
            ]
        );
    }

    public function send(Request $request): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        $captchaValid = (new Validator(
            ['captcha' => $request->body('code')],
            ['captcha' => ['Captcha']]
        ))->isValid();
        $this->session->remove('code');

        if (! $captchaValid) {
            return $this->error(__('Incorrect code'));
        }

        try {
            $this->sendPasswordRecovery->execute(
                new SendRecoveryCommand(
                    nick: $request->body('nick', ''),
                    email: $request->body('email', ''),
                ),
                config('johncms')['homeurl'] ?? ''
            );
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Restore password'),
                'type'    => 'alert-success',
                'message' => __('Check your e-mail for further information'),
            ]
        );
    }

    public function setForm(int $id, string $code): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        try {
            $this->getRecoveryContext->execute($id, $code);
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return new ViewResponse(
            '@profile/public/restore-password-set.twig',
            [
                'form_action' => '/profile/password-recovery/set/' . $id . '/' . $code,
            ]
        );
    }

    public function set(int $id, string $code): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        try {
            $user = $this->getRecoveryContext->execute($id, $code);
            $this->completePasswordRecovery->execute($user);
        } catch (PasswordRecoveryException $e) {
            return $this->error($e->getMessage());
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Restore password'),
                'type'          => 'alert-success',
                'message'       => __('Password successfully changed.<br>New password sent to your E-mail address.'),
                'back_url'      => '/login',
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function error(string $message): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
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
