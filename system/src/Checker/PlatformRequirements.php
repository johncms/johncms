<?php

declare(strict_types=1);

namespace Johncms\Checker;

/** Shared by the installer and the running site; a test keeps the Composer requirements aligned. */
final class PlatformRequirements
{
    public const string PHP = '^8.4';

    public const array EXTENSIONS = ['dom', 'gd', 'json', 'libxml', 'mbstring', 'pdo', 'simplexml', 'zip'];

    /** Runtime requirements in addition to the dependencies declared to Composer. */
    public const array RUNTIME_EXTENSIONS = ['fileinfo', 'pdo_mysql', 'zlib'];
}
