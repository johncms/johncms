<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Services;

use Johncms\System\Http\Session;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ContactsCaptchaService
{
    private const SESSION_KEY = 'code';

    public function __construct(
        private Session $session,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Generates a captcha image and stores the code in the session.
     * Returns an empty string if the image cannot be generated.
     */
    public function generate(): string
    {
        try {
            $code = (new Code())->generate();
            $this->session->set(self::SESSION_KEY, $code);
            return (new Image($code))->generate();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            return '';
        }
    }

    public function forget(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
