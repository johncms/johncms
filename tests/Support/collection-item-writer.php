<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\CollectionWriterDatabase;

CollectionWriterDatabase::boot();
echo \Illuminate\Database\Capsule\Manager::connection()->selectOne('SELECT CONNECTION_ID() AS id')->id . PHP_EOL;
flush();
$form = new CollectionItemFormDTO((int) $argv[1], null, $argv[2], 'Writer', true, null, null, 100, null, null, []);
try {
    CollectionItemWriterFactory::create()->execute(isset($argv[3]) ? (int) $argv[3] : null, $form);
    exit(0);
} catch (CollectionItemCodeAlreadyExistsException) {
    exit(2);
}
