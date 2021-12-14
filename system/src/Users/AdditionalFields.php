<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

class AdditionalFields
{
    public string $telegram = '';
    public string $whatsapp = '';
    public string $website = '';
    public string $about = '';
    public string $status = '';

    public function __construct(array $settings = [])
    {
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $this->$key = htmlspecialchars($value);
            } else {
                $this->$key = $value;
            }
        }
    }
}
