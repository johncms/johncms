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
 * hCaptcha: the visible checkbox, with a puzzle behind it when the visitor looks suspicious.
 *
 * The one of the three that offers somebody it doubts a way to prove themselves, which is what
 * the picture does and what a score cannot.
 */
final class HCaptchaProvider extends AbstractRemoteCaptchaProvider
{
    public const KEY = 'hcaptcha';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return d__('system', 'hCaptcha');
    }

    public function fieldName(): string
    {
        return 'h-captcha-response';
    }

    protected function verifyUrl(): string
    {
        return 'https://api.hcaptcha.com/siteverify';
    }

    protected function template(): string
    {
        return '@theme/components/captcha/hcaptcha.twig';
    }

    protected function verifyPayload(string $answer, string $secret, ?string $clientIp): array
    {
        $payload = ['secret' => $secret, 'response' => $answer, 'sitekey' => $this->siteKey()];

        if ($clientIp !== null) {
            $payload['remoteip'] = $clientIp;
        }

        return $payload;
    }

    protected function judge(array $response): CaptchaResult
    {
        if (($response['success'] ?? false) === true) {
            return CaptchaResult::passed();
        }

        $this->logRefusal($response);

        $errors = (array) ($response['error-codes'] ?? []);

        return CaptchaResult::failed(
            in_array('invalid-or-already-seen-response', $errors, true)
                ? CaptchaFailure::Expired
                : CaptchaFailure::Mismatch
        );
    }
}
