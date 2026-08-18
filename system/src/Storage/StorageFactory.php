<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

/**
 * Builds a disk out of its settings.
 *
 * Adding a driver means adding a case here and to StorageDriver — the vendor packages behind
 * them stay optional, so a site that never touches an object store does not carry its SDK.
 */
final readonly class StorageFactory
{
    /**
     * Permissions used when the configuration names none. What the CMS has always written:
     * files the web server can read, directories it can enter.
     *
     * @var array<string, array<string, int>>
     */
    private const array DEFAULT_PERMISSIONS = [
        'file' => [
            'public'  => 0644,
            'private' => 0600,
        ],
        'dir'  => [
            'public'  => 0755,
            'private' => 0700,
        ],
    ];

    public function create(DiskSettings $settings): StorageInterface
    {
        return match ($settings->driver) {
            StorageDriver::Local => $this->createLocal($settings),
            StorageDriver::S3    => $this->createS3($settings),
        };
    }

    /**
     * An S3-compatible object store.
     *
     * The package is optional, so the check comes first: without it the container would fail
     * with a missing class, and the message has to say what to install instead.
     *
     * No local root is passed on, which is what makes withLocalCopy() download the file to a
     * temporary one — the image processor and getID3 cannot read from a bucket.
     */
    private function createS3(DiskSettings $settings): StorageInterface
    {
        if (! class_exists(AwsS3V3Adapter::class)) {
            throw new UnsupportedStorageDriverException(
                sprintf(
                    'The "%s" disk uses the s3 driver, which needs the league/flysystem-aws-s3-v3 package. '
                    . 'Install it with: composer require league/flysystem-aws-s3-v3',
                    $settings->name
                )
            );
        }

        $options = $settings->options;
        foreach (['bucket', 'region'] as $required) {
            if (empty($options[$required])) {
                throw new UnsupportedStorageDriverException(
                    sprintf('The s3 disk "%s" has no "%s" in its options.', $settings->name, $required)
                );
            }
        }

        $client = new S3Client(
            array_filter(
                [
                    'version'                 => $options['version'] ?? 'latest',
                    'region'                  => $options['region'],
                    'endpoint'                => $options['endpoint'] ?? null,
                    // What the S3-compatible services other than AWS usually need.
                    'use_path_style_endpoint' => $options['path_style'] ?? null,
                    'credentials'             => isset($options['key'], $options['secret'])
                        ? ['key' => $options['key'], 'secret' => $options['secret']]
                        : null,
                ],
                static fn(mixed $value): bool => $value !== null
            )
        );

        $adapter = new AwsS3V3Adapter(
            client: $client,
            bucket: (string) $options['bucket'],
            prefix: (string) ($options['prefix'] ?? ''),
        );

        return new FlysystemStorage(
            filesystem: new Filesystem($adapter),
            visibility: $settings->visibility,
            baseUrl: $settings->url,
        );
    }

    private function createLocal(DiskSettings $settings): StorageInterface
    {
        if ($settings->root === '') {
            throw new UnsupportedStorageDriverException(
                sprintf('The local disk "%s" has no root directory. Set "root" in the configuration.', $settings->name)
            );
        }

        $adapter = new LocalFilesystemAdapter(
            $settings->root,
            PortableVisibilityConverter::fromArray(
                $settings->permissions === [] ? self::DEFAULT_PERMISSIONS : $settings->permissions,
                $settings->visibility
            )
        );

        return new FlysystemStorage(
            filesystem: new Filesystem($adapter),
            visibility: $settings->visibility,
            baseUrl: $settings->url,
            localRoot: rtrim($settings->root, '/\\'),
        );
    }
}
