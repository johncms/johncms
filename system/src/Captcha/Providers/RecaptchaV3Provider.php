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
use Johncms\Captcha\CaptchaSettingField;
use Johncms\Captcha\CaptchaSettingType;

/**
 * Google reCAPTCHA v3.
 *
 * It shows the visitor nothing and scores them instead: 1.0 is certainly a person, 0.0 certainly
 * a script. Which means there is nothing to offer somebody it scores low — they are simply
 * refused — so the threshold is a setting, and a site that would rather give them a second chance
 * points `captcha.default` at the picture and uses this for nothing.
 *
 * v2 is deliberately not shipped: as a visible "I am not a robot" checkbox hCaptcha does the same
 * job without sending the visitor to Google.
 *
 * Note for whoever turns this on: the widget loads scripts from Google on every page carrying a
 * form, which is a decision about the visitors of the site, not only about spam.
 */
final class RecaptchaV3Provider extends AbstractRemoteCaptchaProvider
{
    public const KEY = 'recaptcha_v3';

    /** What Google itself suggests taking as the line between a person and a script. */
    private const DEFAULT_THRESHOLD = 0.5;

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return d__('system', 'Google reCAPTCHA v3');
    }

    public function fieldName(): string
    {
        return 'g-recaptcha-response';
    }

    protected function verifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }

    protected function template(): string
    {
        return '@theme/components/captcha/recaptcha-v3.twig';
    }

    protected function verifyPayload(string $answer, string $secret, ?string $clientIp): array
    {
        $payload = ['secret' => $secret, 'response' => $answer];

        if ($clientIp !== null) {
            $payload['remoteip'] = $clientIp;
        }

        return $payload;
    }

    protected function judge(array $response): CaptchaResult
    {
        if (($response['success'] ?? false) !== true) {
            $this->logRefusal($response);

            // A token is good for two minutes and for one check. A form left open long enough is
            // the ordinary case here, and it is worth telling apart from a refusal.
            $errors = (array) ($response['error-codes'] ?? []);

            return CaptchaResult::failed(
                in_array('timeout-or-duplicate', $errors, true)
                    ? CaptchaFailure::Expired
                    : CaptchaFailure::Mismatch
            );
        }

        $score = (float) ($response['score'] ?? 0.0);

        if ($score < $this->threshold()) {
            $this->logger->info(
                'A submission scored below the reCAPTCHA threshold',
                ['score' => $score, 'threshold' => $this->threshold()]
            );

            return CaptchaResult::failed(CaptchaFailure::LowScore);
        }

        return CaptchaResult::passed();
    }

    protected function extraSettingsFields(): array
    {
        return [
            new CaptchaSettingField(
                key: 'score_threshold',
                type: CaptchaSettingType::Number,
                label: d__('system', 'Score threshold'),
                default: self::DEFAULT_THRESHOLD,
                hint: d__(
                    'system',
                    'Between 0 and 1. Submissions scored below it are refused; raising it stops more bots and more people'
                ),
            ),
        ];
    }

    private function threshold(): float
    {
        return $this->options()->float('score_threshold', self::DEFAULT_THRESHOLD);
    }
}
