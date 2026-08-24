<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\Manifest\ModuleRequirements;
use Johncms\Modules\ModuleCompatibilityChecker;
use PHPUnit\Framework\TestCase;

/**
 * Whether a module may be loaded here. The answer is a sentence rather than a boolean, because
 * every refusal ends up in front of somebody who has to decide what to do about it.
 */
final class ModuleCompatibilityCheckerTest extends TestCase
{
    public function testAModuleThatAsksForNothingIsAlwaysCompatible(): void
    {
        self::assertNull($this->checker()->check($this->manifest(new ModuleRequirements())));
    }

    public function testAModuleAskingForANewerCmsIsRefused(): void
    {
        $problem = $this->checker()->check($this->manifest(new ModuleRequirements(johncms: '^11.0')));

        self::assertSame('Requires JohnCMS ^11.0, and this site is 10.0.', $problem);
    }

    public function testAModuleAskingForANewerPhpIsRefused(): void
    {
        $problem = $this->checker()->check($this->manifest(new ModuleRequirements(php: '^9.0')));

        self::assertSame('Requires PHP ^9.0, and this site runs 8.4.1.', $problem);
    }

    public function testAModuleIsCompatibleWithTheVersionsItAsksFor(): void
    {
        $requires = new ModuleRequirements(php: '>=8.3', johncms: '^10.0');

        self::assertNull($this->checker()->check($this->manifest($requires)));
    }

    public function testAModuleWhoseDependencyIsNotLoadedIsRefused(): void
    {
        $requires = new ModuleRequirements(modules: ['johncms/forum' => '^10.0']);

        $problem = $this->checker()->check($this->manifest($requires), []);

        self::assertSame('Requires the module "johncms/forum", which is not installed or is switched off.', $problem);
    }

    public function testADependencyOfTheWrongVersionIsRefused(): void
    {
        $requires = new ModuleRequirements(modules: ['vasya/blog' => '^2.0']);

        $problem = $this->checker()->check($this->manifest($requires), ['vasya/blog' => '1.4.0']);

        self::assertSame('Requires "vasya/blog" ^2.0, and this site has 1.4.0.', $problem);
    }

    public function testADependencyThatIsThereSatisfiesTheModule(): void
    {
        $requires = new ModuleRequirements(modules: ['vasya/blog' => '^1.2']);

        self::assertNull($this->checker()->check($this->manifest($requires), ['vasya/blog' => '1.4.0']));
    }

    /**
     * PHP on a distribution reports versions such as 8.4.1-1~deb12u1, and a constraint may simply
     * have a typo in it. Neither says anything is wrong with the module, and refusing every module
     * on such a platform would be worse than trusting it.
     */
    public function testAVersionThatCannotBeParsedIsNotHeldAgainstTheModule(): void
    {
        $checker = new ModuleCompatibilityChecker(phpVersion: 'not-a-version', cmsVersion: '10.0');

        self::assertNull($checker->check($this->manifest(new ModuleRequirements(php: '^8.4'))));
    }

    private function checker(): ModuleCompatibilityChecker
    {
        return new ModuleCompatibilityChecker(phpVersion: '8.4.1', cmsVersion: '10.0');
    }

    private function manifest(ModuleRequirements $requires): ModuleManifest
    {
        return new ModuleManifest(
            key: 'vasya/plugin',
            alias: 'plugin',
            path: MODULES_PATH . 'vasya/plugin',
            name: 'Plugin',
            requires: $requires,
        );
    }
}
