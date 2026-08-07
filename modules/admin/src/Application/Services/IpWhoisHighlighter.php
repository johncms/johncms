<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Twig\Markup;

/**
 * Marks up the answer of a whois server: the labels of the well-known fields get a colour, the
 * rest stays the text the server sent.
 *
 * The answer is escaped first and the labels are put in afterwards, so nothing a server sends can
 * reach the page as markup.
 */
final readonly class IpWhoisHighlighter
{
    private const RED = ['inetnum:', 'descr:', 'country:'];

    private const GREEN = ['netname:', 'address:', 'e-mail:', 'person:', 'phone:', 'fax-no:'];

    private const BOLD_RED = ['route:', 'org-name:', 'abuse-mailbox:'];

    private const GRAY = [
        'admin-c:',
        'tech-c:',
        'status:',
        'mnt-by:',
        'mnt-lower:',
        'mnt-routes:',
        'source:',
        'role:',
        'nic-hdl:',
        'org:',
        'remarks:',
        'origin:',
        'organisation:',
        'org-type:',
        'mnt-ref:',
        'NetType:',
        'Comment:',
    ];

    public function highlight(string $answer): Markup
    {
        $escaped = nl2br(htmlspecialchars($answer, ENT_QUOTES, 'UTF-8'));

        return new Markup(strtr($escaped, $this->replacements()), 'UTF-8');
    }

    /**
     * @return array<string, string>
     */
    private function replacements(): array
    {
        $replacements = [];
        foreach (self::RED as $label) {
            $replacements[$label] = '<strong class="red">' . $label . '</strong>';
        }
        foreach (self::GREEN as $label) {
            $replacements[$label] = '<strong class="green">' . $label . '</strong>';
        }
        foreach (self::GRAY as $label) {
            $replacements[$label] = '<strong class="gray">' . $label . '</strong>';
        }
        foreach (self::BOLD_RED as $label) {
            $replacements[$label] = '<strong class="red"><b>' . $label . '</b></strong>';
        }

        return $replacements;
    }
}
