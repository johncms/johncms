<?php

declare(strict_types=1);

namespace Johncms\Http\View;

use Symfony\Component\HttpFoundation\Response;

/**
 * What a controller returns instead of a rendered page: the name of the template and the data it
 * is given.
 *
 * The controller then knows neither the engine nor the environment, and a unit test asserts on
 * the template name and the data without rendering anything.
 */
final readonly class ViewResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $template,
        public array $data = [],
        public int $status = Response::HTTP_OK,
    ) {
    }
}
