<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\View\Twig\Extension\I18nExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * The notice the admin panel prints when the database is behind the code.
 *
 * Rendered from the template the panel actually uses, with the two things it asks about — the
 * permission and the number — answered by the test.
 */
final class PendingMigrationsBannerTest extends TestCase
{
    protected function setUp(): void
    {
        // Nothing registers a translator in an isolated unit test, and the template translates.
        TranslatorFunctions::register(new Translator());
    }

    public function testItSaysHowManyChangesAreWaitingAndWhereToApplyThem(): void
    {
        $html = $this->render(canManageSite: true, pending: 3);

        self::assertStringContainsString('The database needs to be updated', $html);
        self::assertStringContainsString('3 changes', $html);
        self::assertStringContainsString('/admin/maintenance', $html);
    }

    public function testOneWaitingChangeIsSaidInTheSingular(): void
    {
        $html = $this->render(canManageSite: true, pending: 1);

        self::assertStringContainsString('one change', $html);
    }

    public function testNothingIsPrintedWhenTheDatabaseIsUpToDate(): void
    {
        self::assertSame('', trim($this->render(canManageSite: true, pending: 0)));
    }

    /**
     * To somebody who cannot open the maintenance page it would be a notice about something they
     * are unable to act on.
     */
    public function testNothingIsPrintedToSomebodyWhoCouldNotApplyThem(): void
    {
        self::assertSame('', trim($this->render(canManageSite: false, pending: 3)));
    }

    private function render(bool $canManageSite, int $pending): string
    {
        $twig = new Environment(new FilesystemLoader([THEMES_PATH . 'default/templates/admin'], THEMES_PATH), [
            'strict_variables' => true,
        ]);

        $twig->addExtension(new I18nExtension());
        $twig->addFunction(new TwigFunction('can', static fn (string $permission): bool => $canManageSite));
        $twig->addFunction(new TwigFunction('pending_migrations', static fn (): int => $pending));

        return $twig->render('components/pending-migrations.twig');
    }
}
