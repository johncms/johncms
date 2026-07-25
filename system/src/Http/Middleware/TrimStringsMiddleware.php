<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Trims every incoming string in the form request body and the query string.
 *
 * The legacy PSR-7 Request trimmed all input inside filterVar(); HttpFoundation does not.
 * This middleware reproduces that always-trim behaviour in one explicit place, so it applies
 * even when a bag is read directly ($request->request->getString(...)), not only through the
 * Request wrapper helpers. Modeled on Laravel's TrimStrings middleware.
 *
 * Scope matches the historical behaviour: only the form body ($_POST) and query string ($_GET)
 * are trimmed. A raw JSON body is out of scope — HttpFoundation derives it on demand in a
 * fresh InputBag (Request::getPayload()), so there is no persistent bag to normalize, and the
 * legacy Request did not trim it either.
 *
 * Note: passwords are trimmed too, consistent with the historical behaviour (login and
 * registration already trimmed them), so the exception list is intentionally empty for now.
 *
 * It runs first in the global pipeline (see Kernel::handleRaw()) so every controller reads
 * already-trimmed input.
 */
final class TrimStringsMiddleware implements MiddlewareInterface
{
    /**
     * Top-level input keys to leave untouched. Empty by design — see the class docblock.
     *
     * @var list<string>
     */
    private const EXCEPT = [];

    public function handle(Request $request, callable $next): mixed
    {
        $this->trimBag($request->request);
        $this->trimBag($request->query);

        return $next($request);
    }

    private function trimBag(InputBag $bag): void
    {
        $data = $bag->all();

        foreach ($data as $key => $value) {
            if (in_array($key, self::EXCEPT, true)) {
                continue;
            }

            $data[$key] = $this->trimValue($value);
        }

        $bag->replace($data);
    }

    private function trimValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map($this->trimValue(...), $value),
            is_string($value) => trim($value),
            default => $value,
        };
    }
}
