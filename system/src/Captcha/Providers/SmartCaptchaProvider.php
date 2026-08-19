<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha\Providers;

use Johncms\Captcha\CaptchaFailure;
use Johncms\Captcha\CaptchaResult;

/**
 * Yandex SmartCaptcha.
 *
 * Answers with a status rather than with a score, and shows a puzzle to whoever it doubts — so,
 * unlike reCAPTCHA v3, a visitor it is unsure about still has a way through.
 *
 * Its keys are named the other way round from everybody else's: the "client key" goes on the page
 * and the "server key" checks the answer, so the labels of the two fields say which is which.
 */
final class SmartCaptchaProvider extends AbstractRemoteCaptchaProvider
{
    public const KEY = 'smartcaptcha';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return d__('system', 'Yandex SmartCaptcha');
    }

    public function fieldName(): string
    {
        return 'smart-token';
    }

    protected function siteKeyLabel(): string
    {
        return d__('system', 'Client key');
    }

    protected function secretKeyLabel(): string
    {
        return d__('system', 'Server key');
    }

    protected function verifyUrl(): string
    {
        return 'https://smartcaptcha.yandexcloud.net/validate';
    }

    protected function template(): string
    {
        return '@theme/components/captcha/smartcaptcha.twig';
    }

    protected function verifyPayload(string $answer, string $secret, ?string $clientIp): array
    {
        $payload = ['secret' => $secret, 'token' => $answer];

        if ($clientIp !== null) {
            $payload['ip'] = $clientIp;
        }

        return $payload;
    }

    protected function judge(array $response): CaptchaResult
    {
        if (($response['status'] ?? '') === 'ok') {
            return CaptchaResult::passed();
        }

        $this->logRefusal($response);

        return CaptchaResult::failed(CaptchaFailure::Mismatch);
    }
}
