<?php

use Johncms\Api\UserInterface;

/** @var UserInterface $systemUser */
$systemUser = App::getContainer()->get(UserInterface::class);

require __DIR__ . '/includes/' . ($systemUser->isValid() ? 'logout.php' : 'login.php');
