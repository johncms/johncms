<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One setting of one captcha provider, as the settings page draws it.
 *
 * A provider describes its settings and the page renders them, so a captcha a module brought
 * along gets its form without a line written here.
 */
final readonly class CaptchaSettingRowDTO
{
    /**
     * @param string                $type    text, password, number, checkbox or select.
     * @param bool                  $hasValue Whether a secret is stored. The secret itself is
     *                                        never sent back: an input holding it would put it in
     *                                        every page cache and every screenshot of this screen.
     * @param array<string, string> $options Values of a select, as value => label.
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public string $hint = '',
        public string $value = '',
        public bool $checked = false,
        public bool $hasValue = false,
        public array $options = [],
    ) {
    }
}
