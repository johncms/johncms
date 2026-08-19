<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

/**
 * The kinds of input the settings page can draw for a provider.
 *
 * Small on purpose: a provider describing its settings with these needs no template of its own,
 * which is the whole point of the list — a module ships a class and gets a settings form.
 */
enum CaptchaSettingType: string
{
    case Text = 'text';

    /** Never sent back to the browser: the page shows whether it is filled, not what it holds. */
    case Password = 'password';

    case Number = 'number';

    case Checkbox = 'checkbox';

    case Select = 'select';
}
