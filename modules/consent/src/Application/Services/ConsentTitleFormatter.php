<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Prepares a consent title for output.
 *
 * The title is shown next to the checkbox in a form and may contain inline HTML,
 * typically links to other pages. It is stored raw and sanitized on output with an
 * inline-only allowlist. Places where markup makes no sense (page titles, breadcrumbs,
 * admin tables) use the plain text variant instead.
 */
final readonly class ConsentTitleFormatter
{
    private const ALLOWED_HTML = 'a[href|title|target|rel],b,strong,i,em,u,br';

    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', self::ALLOWED_HTML);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.Linkify', false);

        $this->purifier = new HTMLPurifier($config);
    }

    public function toHtml(string $title): string
    {
        return $this->purifier->purify($title);
    }

    public function toPlainText(string $title): string
    {
        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
