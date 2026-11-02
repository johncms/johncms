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
$mode = $argv[4] ?? 'save';
try {
    if ($mode === 'section' || $mode === 'move-section') {
        $form = new \Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO(
            (int) $argv[1], $mode === 'move-section' ? (int) $argv[2] : null,
            $mode === 'move-section' ? 'section-' . $argv[3] : $argv[2], 'Section', null, true, 100
        );
        CollectionItemWriterFactory::sectionWriter()->execute($mode === 'move-section' ? (int) $argv[3] : null, $form);
    } elseif ($mode === 'views') {
        $api = CollectionItemWriterFactory::api();
        for ($i = 0; $i < (int) $argv[2]; ++$i) {
            $api->incrementViews((int) $argv[3]);
        }
    } elseif ($mode === 'values') {
        CollectionItemWriterFactory::api()->patchItem((int) $argv[3],
            new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(values: [$argv[2] => 'Patched'])
        );
    } else {
        $form = new CollectionItemFormDTO((int) $argv[1], null, $argv[2], 'Writer', true, null, null, 100, null, null, []);
        CollectionItemWriterFactory::create()->execute(isset($argv[3]) ? (int) $argv[3] : null, $form);
    }
    exit(0);
} catch (CollectionItemCodeAlreadyExistsException | \Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException | \Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException) {
    exit(2);
}
