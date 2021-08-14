<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forms\Inputs;

use Johncms\Http\Session;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

class Captcha extends AbstractInput
{
    public string $type = 'captcha';
    public string $code = '';
    public ?Image $image = null;

    public function generateCode(): Captcha
    {
        $code = (string) new Code();
        di(Session::class)->set(\Johncms\Validator\Rules\Captcha::SESSION_FIELD, $code);
        $this->code = $code;
        $this->image = new Image($code);
        return $this;
    }
}
