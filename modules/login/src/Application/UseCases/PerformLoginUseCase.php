<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\UseCases;

use Illuminate\Support\Str;
use Johncms\Modules\Login\Application\DTO\LoginResultDTO;
use Johncms\Modules\Login\Domain\Enums\LoginStatus;
use Johncms\Users\User;
use Johncms\Http\Session;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

final class PerformLoginUseCase
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function execute(string $userLogin, string $userPass, string $captchaCode = ''): LoginResultDTO
    {
        $loginUser = User::query()->where('name_lat', Str::slug($userLogin, '_'))->first();

        if ($loginUser === null) {
            return new LoginResultDTO(LoginStatus::Error, [__('Authorization failed')]);
        }

        if ($loginUser->failed_login > 2) {
            if (! $captchaCode) {
                $code = (string) new Code();
                $this->session->set('code', $code);

                return new LoginResultDTO(
                    LoginStatus::CaptchaRequired,
                    captcha: new Image($code),
                );
            }

            if (mb_strlen($captchaCode) < 3 || strtolower($captchaCode) !== strtolower((string) ($this->session->get('code', '')))) {
                $this->session->remove('code');
                return new LoginResultDTO(LoginStatus::Error, [__('The security code is not correct')]);
            }

            $this->session->remove('code');
        }

        if (md5(md5($userPass)) !== $loginUser->password) {
            if ($loginUser->failed_login < 3) {
                $loginUser->update(['failed_login' => $loginUser->failed_login + 1]);
            }

            return new LoginResultDTO(LoginStatus::Error, [__('Authorization failed')]);
        }

        $loginUser->update(['failed_login' => 0]);

        if (! $loginUser->email_confirmed && config('johncms')['user_email_confirmation']) {
            return new LoginResultDTO(LoginStatus::EmailNotConfirmed);
        }

        if (! $loginUser->preg) {
            return new LoginResultDTO(LoginStatus::ModerationPending);
        }

        $loginUser->update(['sestime' => time()]);

        return new LoginResultDTO(
            LoginStatus::Success,
            userId: $loginUser->id,
            passwordHash: md5($userPass),
        );
    }
}
