<?php

declare(strict_types=1);

namespace Tests\Unit\System\i18n;

use Gettext\Translations;
use Johncms\System\i18n\AdminTaskScanner;
use PHPUnit\Framework\TestCase;

final class AdminTaskScannerTest extends TestCase
{
    public function testTheTitleAndTheDescriptionOfATaskAreCollectedIntoItsDomain(): void
    {
        $system = Translations::create('system');
        $file = ROOT_PATH . 'system/src/Console/Commands/MigrateCommand.php';

        (new AdminTaskScanner(['system' => $system]))->scanFile($file);

        $title = $system->find(null, 'Update the database');
        self::assertNotNull($title);
        self::assertSame([$file], array_keys($title->getReferences()->toArray()));
        self::assertNotNull($system->find(null, 'Brings the database up to what the files of the site expect. Safe to run more than once: what has already been applied is skipped.'));
    }

    public function testAFileWithoutATaskAddsNothing(): void
    {
        $system = Translations::create('system');

        (new AdminTaskScanner(['system' => $system]))->scanFile(ROOT_PATH . 'system/src/AdminTasks/AdminTaskRegistry.php');

        self::assertCount(0, $system);
    }
}
