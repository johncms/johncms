<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

use Johncms\Http\Session;
use Mobicms\Captcha\Code;

/**
 * The verification code the sign-in forms ask for after repeated failures.
 *
 * Issuing the code and checking the answer belong together — they are two halves of one session
 * key — so both screens share this instead of each keeping its own copy of the key name.
 *
 * The picture is not built here: the two forms render it differently, and drawing is not this
 * object's job.
 */
final readonly class LoginCaptcha
{
    private const SESSION_KEY = 'code';

    private const MIN_LENGTH = 3;

    public function __construct(private Session $session)
    {
    }

    /**
     * A fresh code, remembered for the next submission.
     */
    public function issue(): string
    {
        $code = (string) new Code();
        $this->session->set(self::SESSION_KEY, $code);

        return $code;
    }

    /**
     * Whether the answer matches the code that was issued. The code is spent either way: a
     * wrong answer must not leave the same picture answerable a second time.
     */
    public function verify(string $answer): bool
    {
        $expected = (string) $this->session->get(self::SESSION_KEY, '');
        $this->session->remove(self::SESSION_KEY);

        if ($expected === '' || mb_strlen($answer) < self::MIN_LENGTH) {
            return false;
        }

        return mb_strtolower($answer) === mb_strtolower($expected);
    }
}
