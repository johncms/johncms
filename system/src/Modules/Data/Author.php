<?php

declare(strict_types=1);

namespace Johncms\Modules\Data;

class Author
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $homepage = '',
        public string $role = '',
    ) {
    }
}
