<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

// The disks the CMS stores files on, reached through Johncms\Storage\StorageInterface.
// Override in filesystem.local.php — that file is not in the repository, so a server-specific
// disk survives an update of the CMS.
return [
    'filesystem' => [
        // Disk used when a caller names none. It is also the name written into `files.storage`,
        // so renaming a disk means updating the rows that point at it.
        'default' => 'local',

        // Every disk needs a driver; an unknown one is refused when the settings are read.
        // Supported: local.
        'disks' => [
            'local' => [
                'driver' => 'local',

                // Root directory. Everything below is relative to it.
                'root' => UPLOAD_PATH,

                // Base URL the files are served at. Empty means the disk is not public: its
                // files are then streamed by the /file/{id} controller instead, and the URLs the
                // modules render change to it on their own.
                //
                // A private disk is how attachments stop being reachable by their address alone.
                // Point its root somewhere outside public/, for example:
                //
                //   'attachments' => [
                //       'driver'     => 'local',
                //       'root'       => DATA_PATH . 'attachments',
                //       'url'        => '',
                //       'visibility' => 'private',
                //   ],
                'url' => '/upload',

                // Visibility given to what is written: public or private. It is applied on every
                // write, because the local adapter otherwise leaves the mode to umask — on a
                // server with a strict one that means files the web server cannot read.
                'visibility' => 'public',

                // Modes the visibility above translates to. Leave them alone unless the hosting
                // needs something else; group-writable directories, for one, are what some
                // shared hostings require.
                'permissions' => [
                    'file' => [
                        'public'  => 0644,
                        'private' => 0600,
                    ],
                    'dir'  => [
                        'public'  => 0755,
                        'private' => 0700,
                    ],
                ],
            ],
        ],
    ],
];
