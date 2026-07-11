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
use Johncms\Cache\RaceSafeFilesystem;

/**
 * Class Cache
 *
 * @package Johncms
 *
 * @method getPrefix()
 * @method flush()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Cache extends Repository
{
    public function __construct()
    {
        parent::__construct(new FileStore(new RaceSafeFilesystem(), DATA_PATH . 'cache/johncms'));
    }

    public function __invoke(): Cache
    {
        return new self();
    }

    public static function create(): self
    {
        return new self();
    }
}
