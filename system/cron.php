<?php

declare(strict_types=1);

use Johncms\Mail\EmailSender;

define('CONSOLE_MODE', true);

require 'bootstrap.php';

EmailSender::send();
