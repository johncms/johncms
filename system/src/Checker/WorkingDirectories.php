<?php

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Cache\CacheDriver;
use Johncms\Cache\CacheSettings;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageSettings;

final readonly class WorkingDirectories
{
    public function __construct(private StorageSettings $storage, private CacheSettings $cache, private PhpEnvironmentInterface $php)
    {
    }

    /** @return array<string, string> Stable identifiers mapped to the configured directories. */
    public function all(): array
    {
        $paths = [
            'cache' => CACHE_PATH,
            'logs' => LOG_PATH,
            'scheduler' => DATA_PATH . 'schedule',
            'temporary' => $this->php->ini('upload_tmp_dir') ?: sys_get_temp_dir(),
        ];
        if ($this->cache->driver === CacheDriver::Filesystem) {
            $paths['cache.storage'] = $this->cache->directory ?? CACHE_PATH . 'app';
        }
        foreach ($this->storage->disks as $name => $disk) {
            if ($disk->driver === StorageDriver::Local) {
                $paths['storage.' . $name] = $disk->root;
            }
        }

        return $paths;
    }
}
