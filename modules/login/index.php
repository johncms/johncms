<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\UserInterface;

/** @var UserInterface $systemUser */
$systemUser = App::getContainer()->get(UserInterface::class);

require __DIR__ . '/includes/' . ($systemUser->isValid() ? 'logout.php' : 'login.php');
