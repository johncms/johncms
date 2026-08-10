<?php

/**
 * CSRF protection.
 *
 * Every request with an unsafe method (POST, PUT, PATCH, DELETE) must carry the token, either
 * as a csrf_token body field or as an X-CSRF-Token header.
 *
 * The regular way to exempt an endpoint is Route::withoutCsrf() next to its declaration. This
 * list is for the entry points whose routes are not ours to edit, and as an emergency hatch
 * that needs no deploy. Paths are matched against the normalized request path (decoded, with
 * no trailing slash) and accept shell wildcards:
 *
 *     'except' => ['/legacy/endpoint', '/api/*'],
 */

declare(strict_types=1);

return [
    'except' => [],
];
