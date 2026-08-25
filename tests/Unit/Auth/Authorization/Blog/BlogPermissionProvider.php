<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization\Blog;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * The permissions of the module under test. It lives in a namespace of its own, which is exactly
 * how the purger tells its permissions from anybody else's.
 */
final class BlogPermissionProvider implements PermissionProviderInterface
{
    public function permissions(): iterable
    {
        return [
            new PermissionDefinition('blog.manage', 'blog', 'Manage the blog'),
            new PermissionDefinition('blog.comments.post', 'blog', 'Comment on the blog'),
        ];
    }
}
