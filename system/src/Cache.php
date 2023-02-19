<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;

/**
 * @method getPrefix()
 * @method flush()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Cache extends Repository
{
    public function __construct()
    {
        parent::__construct(new FileStore(new Filesystem(), DATA_PATH . 'cache/johncms'));
    }
}
