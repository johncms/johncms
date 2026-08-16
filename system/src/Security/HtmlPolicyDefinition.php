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

use InvalidArgumentException;

/**
 * A policy a module declares for content of its own: what its markup is allowed to contain,
 * said without naming any sanitizing library.
 *
 * Everything is an allow list. What is not named here does not survive, so a policy can only
 * ever be as permissive as what it enumerates — an empty definition strips markup entirely.
 */
final readonly class HtmlPolicyDefinition
{
    /**
     * Elements that carry behaviour or pull in a resource rather than format text. Allowing one
     * is never what a policy for user content means, so it is refused where it is written
     * instead of being quietly dropped later.
     */
    private const FORBIDDEN_ELEMENTS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'textarea', 'select', 'option',
        'base', 'meta', 'link', 'svg', 'math',
    ];

    /**
     * `javascript:`, `data:` and the like put executable content where an address is expected.
     */
    private const FORBIDDEN_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    /**
     * @param string $name Unique id of the policy, `<module>.<content>` by convention
     * @param array<string, list<string>> $elements Allowed elements, each with its allowed attributes
     * @param list<string>|null $allowedClasses Class names that survive in a `class` attribute; null lets any through
     * @param bool $linkify Whether a bare URL in the text becomes a link
     * @param list<string> $linkSchemes URL schemes a link may use
     * @param list<string> $frameTargets Values allowed in a `target` attribute
     */
    public function __construct(
        public string $name,
        public array $elements,
        public ?array $allowedClasses = [],
        public bool $linkify = false,
        public array $linkSchemes = ['http', 'https', 'mailto'],
        public array $frameTargets = ['_blank'],
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('An HTML policy needs a name.');
        }

        foreach ($elements as $element => $attributes) {
            if (! is_string($element) || trim($element) === '') {
                throw new InvalidArgumentException(
                    sprintf('The policy "%s" declares an element without a name.', $name)
                );
            }

            if (in_array(strtolower($element), self::FORBIDDEN_ELEMENTS, true)) {
                throw new InvalidArgumentException(
                    sprintf('The policy "%s" may not allow <%s>: it is not text formatting.', $name, $element)
                );
            }

            foreach ($attributes as $attribute) {
                if (! is_string($attribute) || trim($attribute) === '') {
                    throw new InvalidArgumentException(
                        sprintf('The policy "%s" declares an unnamed attribute on <%s>.', $name, $element)
                    );
                }

                // onclick, onerror, onload — an event handler is code, whatever it is attached to.
                if (str_starts_with(strtolower($attribute), 'on')) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'The policy "%s" may not allow the event handler "%s" on <%s>.',
                            $name,
                            $attribute,
                            $element
                        )
                    );
                }
            }
        }

        foreach ($linkSchemes as $scheme) {
            if (in_array(strtolower($scheme), self::FORBIDDEN_SCHEMES, true)) {
                throw new InvalidArgumentException(
                    sprintf('The policy "%s" may not allow the "%s" scheme in a link.', $name, $scheme)
                );
            }
        }
    }
}
