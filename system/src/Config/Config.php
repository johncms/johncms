<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Config;

use Johncms\Api\ConfigInterface;
use Zend\Stdlib\ArrayObject;

class Config extends ArrayObject implements ConfigInterface
{
    public function __construct(array $input)
    {
        parent::__construct($input, parent::ARRAY_AS_PROPS);
    }
}
