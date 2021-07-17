<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use InvalidArgumentException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use RuntimeException;

/**
 * Class Filesystem
 *
 * @package Johncms\Files
 */
class Filesystem
{
    /** @var \League\Flysystem\Filesystem[] */
    protected $storages = [];

    /** @var array */
    protected $config;

    public function __construct()
    {
        $this->config = di('config')['filesystem'];
    }

    public function __invoke(): Filesystem
    {
        return new Filesystem();
    }

    public function storage(string $storage_name = 'local'): \League\Flysystem\Filesystem
    {
        if (array_key_exists($storage_name, $this->storages)) {
            return $this->storages[$storage_name];
        }

        if (! array_key_exists($storage_name, $this->config['storages'])) {
            throw new InvalidArgumentException(sprintf('The "%s" storage settings are not specified.', $storage_name));
        }

        $settings = $this->config['storages'][$storage_name];
        switch ($settings['type']) {
            default:
                if (empty($settings['root_dir'])) {
                    throw new RuntimeException('The root directory is not set in the storage settings.');
                }
                $adapter = new LocalFilesystemAdapter(
                    $settings['root_dir'],
                    PortableVisibilityConverter::fromArray(
                        [
                            'file' => [
                                'public'  => 0640,
                                'private' => 0640,
                            ],
                            'dir'  => [
                                'public'  => 0755,
                                'private' => 0755,
                            ],
                        ]
                    )
                );
                break;
        }

        $this->storages[$storage_name] = new \League\Flysystem\Filesystem($adapter);
        return $this->storages[$storage_name];
    }
}
