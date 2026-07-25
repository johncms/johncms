<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Thin JohnCMS wrapper over Symfony HttpFoundation Request.
 *
 * Charter (see .claude/http-kernel-migration-plan.md, stage 1a):
 *   - no get* methods (the whole get* surface belongs to HttpFoundation);
 *   - no own request state (route params live in $request->attributes);
 *   - thin delegates only, no $filter/$options in signatures, no business logic;
 *   - never duplicate what HttpFoundation already provides (isSecure(), getClientIp(), ...).
 *
 * The body and query helpers cover the only three input shapes found in the codebase —
 * string, int and int-list. Invalid input is handled softly: bodyInt()/queryInt() catch
 * HttpFoundation's BadRequestException and return the default, preserving the historical
 * behaviour of the former filterVar()-based Request. Strict semantics stay available by
 * calling the bags directly ($request->query->getInt('id')).
 *
 * Note: trimming is intentionally NOT done here. HttpFoundation does not trim, and the
 * historical always-trim behaviour is reproduced once, in TrimStringsMiddleware (stage 1a-bis).
 */
final class Request extends BaseRequest
{
    /**
     * Cached decoded body. getPayload() clones the request bag on every call, so the
     * result is memoized here. This is the only state the wrapper is allowed to hold —
     * a cache, not request data.
     */
    private ?InputBag $payload = null;

    /** Read a string from the request body (form-encoded or JSON). */
    public function body(string $key, string $default = ''): string
    {
        return $this->payload()->getString($key, $default);
    }

    /** Read an integer from the request body; invalid input falls back to the default. */
    public function bodyInt(string $key, int $default = 0): int
    {
        return $this->softInt($this->payload(), $key, $default);
    }

    /**
     * Read a plain (unfiltered) list from the request body, e.g. users[], post_ids[], ids[].
     *
     * @return array<int|string, mixed>
     */
    public function bodyList(string $key): array
    {
        return $this->payload()->all($key);
    }

    /**
     * Read a list of integers from the request body, e.g. attached_files[].
     * Non-integer elements become null (HttpFoundation applies FILTER_NULL_ON_FAILURE).
     *
     * @return array<int|string, int|null>
     */
    public function bodyInts(string $key): array
    {
        return $this->payload()->filter($key, [], FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);
    }

    /** Whether the request body contains the given key (used for checkbox-style flags). */
    public function hasBody(string $key): bool
    {
        return $this->payload()->has($key);
    }

    /** Read a string from the query string. */
    public function queryParam(string $key, string $default = ''): string
    {
        return $this->query->getString($key, $default);
    }

    /** Read an integer from the query string; invalid input falls back to the default. */
    public function queryInt(string $key, int $default = 0): int
    {
        return $this->softInt($this->query, $key, $default);
    }

    /**
     * Read a list of integers from the query string.
     * Non-integer elements become null (HttpFoundation applies FILTER_NULL_ON_FAILURE).
     *
     * @return array<int|string, int|null>
     */
    public function queryInts(string $key): array
    {
        return $this->query->filter($key, [], FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);
    }

    /** Whether the current request uses the POST method. */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    private function payload(): InputBag
    {
        return $this->payload ??= $this->getPayload();
    }

    /**
     * Read an integer with the "soft" invalid-input policy: a failed filter (e.g. ?id=abc)
     * falls back to the default, reproducing the legacy Request. An array in a scalar
     * parameter (e.g. ?id[]=1) is a type-confusion attempt and is deliberately NOT caught —
     * it surfaces as BadRequestException, which the kernel maps to a 400 (plan stage 1a/3).
     */
    private function softInt(InputBag $bag, string $key, int $default): int
    {
        if (is_array($bag->all()[$key] ?? null)) {
            return $bag->getInt($key, $default);
        }

        try {
            return $bag->getInt($key, $default);
        } catch (BadRequestException) {
            return $default;
        }
    }
}
