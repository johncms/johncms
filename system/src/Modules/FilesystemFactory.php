<?php

declare(strict_types=1);

namespace Johncms\Modules;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class FilesystemFactory
{
    public static function create(): Filesystem
    {
        $adapter = new LocalFilesystemAdapter(
            ROOT_PATH,
            PortableVisibilityConverter::fromArray(
                [
                    'file' => [
                        'public'  => 0644,
                        'private' => 0644,
                    ],
                    'dir'  => [
                        'public'  => 0755,
                        'private' => 0755,
                    ],
                ]
            ),
            LOCK_EX,
            LocalFilesystemAdapter::DISALLOW_LINKS
        );

        return new Filesystem($adapter);
    }
}
