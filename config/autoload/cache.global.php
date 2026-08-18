<?php

declare(strict_types=1);

// Application cache defaults. Override in cache.local.php — that file is not in the repository,
// so a server-specific driver survives an update of the CMS.
//
// This is the cache the modules write through Johncms\Cache\CacheInterface. It has nothing to do
// with the compiled container, the routes or the Twig cache: those are configured by the
// CACHE_CONTAINER, CACHE_ROUTES and CACHE_TEMPLATES constants and cleared by `cache:clear`.
return [
    'cache' => [
        // Storage the cache is built on:
        //   filesystem — files under data/cache, works everywhere;
        //   apcu       — shared memory of the PHP process, one web server only;
        //   redis      — for an installation spread over several servers;
        //   array      — in memory for one request, meant for tests;
        //   null       — stores nothing, every read is a miss.
        'driver' => 'filesystem',

        // Prefix isolating this installation inside the storage. Only -+_. and alphanumerics.
        // Defaults to the version of the CMS, so an upgrade starts from an empty cache instead
        // of reading entries written in the format of the previous release.
        'namespace' => null,

        // Lifetime of an entry that asks for none, in seconds. Zero keeps it until a tag
        // invalidates it or the cache is cleared — which is what the tagged entries rely on.
        'default_lifetime' => 0,

        // Where the filesystem driver writes. null means data/cache/app.
        'directory' => null,

        // How the filesystem driver relates tags to entries:
        //   auto    — symlinks where the hosting allows them, plain files otherwise;
        //   symlink — force symlinks;
        //   files   — force plain files: slower to invalidate, but works anywhere.
        // Leave it on auto unless the probe gets it wrong on your hosting.
        'tags_storage' => 'auto',

        'redis' => [
            'dsn' => 'redis://127.0.0.1:6379',
        ],
    ],
];
