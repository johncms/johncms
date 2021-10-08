<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http\Response;

use GuzzleHttp\Psr7\Response;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

class RedirectResponse extends Response
{
    protected Session $session;

    public function __construct(string $uri, int $status = 302, array $headers = [])
    {
        $this->session = di(Session::class);
        $headers['location'] = $uri;
        parent::__construct($status, $headers);
    }

    public function withPost(): RedirectResponse
    {
        $request = di(Request::class);
        $this->session->flash(Request::POST_SESSION_KEY, $request->getParsedBody());
        return $this;
    }

    public function withValidationErrors(array $errors): RedirectResponse
    {
        $this->session->flash(Validator::VALIDATION_ERRORS_KEY, $errors);
        return $this;
    }
}
