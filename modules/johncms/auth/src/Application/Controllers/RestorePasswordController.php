<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Controllers;

use Johncms\Captcha\CaptchaManager;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Auth\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Auth\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Auth\Application\UseCases\CompletePasswordRecoveryUseCase;
use Johncms\Modules\Auth\Application\UseCases\GetRecoveryContextUseCase;
use Johncms\Modules\Auth\Application\UseCases\SendPasswordRecoveryUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\ValidatorInterface;

final readonly class RestorePasswordController
{
    private const CAPTCHA_SCOPE = 'password_recovery';

    public function __construct(
        private NavChain $navChain,
        private SendPasswordRecoveryUseCase $sendPasswordRecovery,
        private GetRecoveryContextUseCase $getRecoveryContext,
        private CompletePasswordRecoveryUseCase $completePasswordRecovery,
        private CaptchaManager $captcha,
        private ValidatorInterface $validator,
    ) {
    }

    public function form(): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        return new ViewResponse(
            '@auth/public/restore-password.twig',
            [
                'title'      => __('Restore password'),
                'page_title' => __('Restore password'),
                'captcha'    => $this->captcha->challenge(self::CAPTCHA_SCOPE),
            ]
        );
    }

    public function send(Request $request): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        // The answer is spent by being checked, so a failed attempt cannot be replayed against
        // the same picture; the form issues a new one.
        $captchaValid = $this->validator
            ->validate(
                ['captcha' => $request->body($this->captcha->fieldName(), '')],
                ['captcha' => [new Captcha(scope: self::CAPTCHA_SCOPE)]]
            )
            ->isValid();

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
            '@auth/public/restore-password-set.twig',
            [
                'title'       => __('Restore password'),
                'page_title'  => __('Restore password'),
                'form_action' => '/password-recovery/' . $id . '/' . $code,
            ]
        );
    }

    public function set(int $id, string $code): ViewResponse
    {
        $this->navChain->add(__('Restore password'));

        try {
            $user = $this->getRecoveryContext->execute($id, $code);
            $this->completePasswordRecovery->execute($user, $code);
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
                'back_url'      => '/password-recovery',
                'back_url_name' => __('Back'),
            ]
        );
    }
}
