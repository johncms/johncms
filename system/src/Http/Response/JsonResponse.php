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

class JsonResponse extends Response
{
    public function __construct(array $data, int $status = 200)
    {
        parent::__construct($status);
        $this->getBody()->write(json_encode($data));
        $this->withAddedHeader('content-type', 'application/json');
    }
}
