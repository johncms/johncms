<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

/**
 * The HTMLPurifier-backed sanitizer.
 *
 * One purifier per policy, built when the policy is first used: a page that only renders posts
 * never pays for the configuration of the other two.
 */
final class HtmlSanitizer implements HtmlSanitizerInterface
{
    /** @var array<string, \HTMLPurifier> */
    private array $purifiers = [];

    public function __construct(
        private readonly HtmlPurifierFactory $purifierFactory,
    ) {
    }

    public function sanitize(string $html, HtmlPolicy $policy = HtmlPolicy::RichContent): string
    {
        if ($html === '') {
            return '';
        }

        return $this->purifier($policy)->purify($html);
    }

    public function toPlainText(string $html, HtmlPolicy $policy = HtmlPolicy::RichContent): string
    {
        // Sanitized first: stripping the tags of markup nobody validated would turn the content
        // of a <script> into visible text.
        $text = strip_tags($this->sanitize($html, $policy));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function purifier(HtmlPolicy $policy): \HTMLPurifier
    {
        return $this->purifiers[$policy->name] ??= $this->purifierFactory->create($policy);
    }
}
