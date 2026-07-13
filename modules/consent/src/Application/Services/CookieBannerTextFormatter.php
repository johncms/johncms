<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Prepares the cookie banner text for output.
 *
 * The text is entered by an administrator and may contain inline HTML, typically links to
 * the cookie policy and the consent pages. It is stored raw and sanitized on output with
 * an inline-only allowlist.
 */
final readonly class CookieBannerTextFormatter
{
    private const ALLOWED_HTML = 'a[href|title|target|rel],b,strong,i,em,u,br,p,span';

    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', self::ALLOWED_HTML);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.Linkify', false);
        $config->set('AutoFormat.AutoParagraph', false);

        $this->purifier = new HTMLPurifier($config);
    }

    public function toHtml(string $text): string
    {
        return $this->purifier->purify($text);
    }
}
