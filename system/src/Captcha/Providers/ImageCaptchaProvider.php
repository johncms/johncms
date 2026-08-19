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

use Johncms\Captcha\CaptchaChallenge;
use Johncms\Captcha\CaptchaFailure;
use Johncms\Captcha\CaptchaProviderInterface;
use Johncms\Captcha\CaptchaProviderOptions;
use Johncms\Captcha\CaptchaResult;
use Johncms\Captcha\CaptchaSettingField;
use Johncms\Captcha\CaptchaSettingType;
use Johncms\Http\Session;
use Mobicms\Captcha\Image;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The captcha the CMS ships with: a code drawn into a picture and kept in the session.
 *
 * It asks nobody for permission and sends nothing anywhere, which is why it is also what the
 * manager falls back to when a remote service is configured but unusable.
 */
final readonly class ImageCaptchaProvider implements CaptchaProviderInterface
{
    public const KEY = 'image';

    /**
     * The answers of the open forms live side by side under this prefix. One shared key is what
     * made a registration form and a sign-in screen open in two tabs erase each other's code.
     */
    private const SESSION_PREFIX = 'captcha.';

    public function __construct(
        private Session $session,
        private LoggerInterface $logger,
    ) {
    }

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return d__('system', 'Picture with a code');
    }

    /**
     * Nothing to configure and nothing to reach: it works as soon as GD is there, and GD is a
     * requirement of the CMS itself.
     */
    public function isConfigured(): bool
    {
        return extension_loaded('gd');
    }

    public function settingsFields(): array
    {
        return [
            new CaptchaSettingField(
                key: 'width',
                type: CaptchaSettingType::Number,
                label: d__('system', 'Picture width'),
                default: 190,
                hint: d__('system', 'A narrow picture crowds the characters together and makes them hard to read'),
            ),
            new CaptchaSettingField(
                key: 'height',
                type: CaptchaSettingType::Number,
                label: d__('system', 'Picture height'),
                default: 90,
            ),
            new CaptchaSettingField(
                key: 'format',
                type: CaptchaSettingType::Select,
                label: d__('system', 'Picture format'),
                default: 'png',
                options: ['png' => 'PNG', 'webp' => 'WebP', 'gif' => 'GIF'],
            ),
            new CaptchaSettingField(
                key: 'length_min',
                type: CaptchaSettingType::Number,
                label: d__('system', 'Minimum code length'),
                default: 4,
            ),
            new CaptchaSettingField(
                key: 'length_max',
                type: CaptchaSettingType::Number,
                label: d__('system', 'Maximum code length'),
                default: 5,
            ),
        ];
    }

    public function fieldName(): string
    {
        return 'code';
    }

    public function challenge(string $scope): CaptchaChallenge
    {
        $image = $this->image();
        $picture = '';

        try {
            $this->session->set($this->sessionKey($scope), $image->getCode());
            $picture = $image->getImage();
        } catch (Throwable $exception) {
            // A picture that could not be drawn is not a reason to answer the request with an
            // error page: the form is shown, the field stays, and the submission fails the check.
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }

        return new CaptchaChallenge(
            provider: self::KEY,
            template: '@theme/components/captcha/image.twig',
            fieldName: $this->fieldName(),
            params: [
                'image'  => $picture,
                'length' => $image->lengthMax,
            ],
        );
    }

    public function verify(string $answer, string $scope, ?string $clientIp = null): CaptchaResult
    {
        $key = $this->sessionKey($scope);
        $expected = (string) $this->session->get($key, '');

        // Spent whichever way the comparison goes: a wrong answer must not leave the same
        // picture answerable a second time.
        $this->session->remove($key);

        $answer = trim($answer);

        if ($answer === '') {
            return CaptchaResult::failed(CaptchaFailure::Missing);
        }

        if ($expected === '') {
            return CaptchaResult::failed(CaptchaFailure::Expired);
        }

        return mb_strtolower($answer) === mb_strtolower($expected)
            ? CaptchaResult::passed()
            : CaptchaResult::failed(CaptchaFailure::Mismatch);
    }

    private function image(): Image
    {
        $options = CaptchaProviderOptions::forProvider(self::KEY);

        $image = new Image();
        $image->imageWidth = $options->int('width', $image->imageWidth);
        $image->imageHeight = $options->int('height', $image->imageHeight);
        $image->imageFormat = $options->string('format', $image->imageFormat);
        $image->lengthMin = $options->int('length_min', $image->lengthMin);
        $image->lengthMax = $options->int('length_max', $image->lengthMax);

        return $image;
    }

    private function sessionKey(string $scope): string
    {
        return self::SESSION_PREFIX . $scope;
    }
}
