<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleAssets;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleAssetPublisher;
use PHPUnit\Framework\TestCase;

/**
 * Getting the files of a module into the document root — and, more importantly, only those files.
 * Everything under the document root is served by the web server, so what is copied there decides
 * what a visitor can reach.
 */
final class ModuleAssetPublisherTest extends TestCase
{
    private string $root;

    private string $publicPath;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-assets-' . uniqid() . DS;
        $this->publicPath = $this->root . 'public' . DS;
        mkdir($this->publicPath, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testTheAssetsOfAModuleEndUpUnderItsAlias(): void
    {
        $manifest = $this->module('vasya/blog', 'blog', [
            'public/js/app.js'      => 'console.log(1);',
            'public/css/app.css'    => '.a{}',
            'public/images/pic.png' => 'binary',
        ]);

        $published = $this->publisher()->publish($manifest);

        self::assertSame(3, $published);
        self::assertFileExists($this->publicPath . 'modules/blog/js/app.js');
        self::assertFileExists($this->publicPath . 'modules/blog/css/app.css');
        self::assertFileExists($this->publicPath . 'modules/blog/images/pic.png');
    }

    /**
     * A .php among the assets of a module is code the module put where anyone can run it. A module
     * that needs to answer a request declares a route.
     */
    public function testOnlyFilesABrowserLoadsArePublished(): void
    {
        $manifest = $this->module('vasya/blog', 'blog', [
            'public/js/app.js'         => 'console.log(1);',
            'public/tools/backdoor.php' => '<?php echo 1;',
            'public/notes.md'          => 'text',
            'public/archive.phar'      => 'binary',
        ]);

        $published = $this->publisher()->publish($manifest);

        self::assertSame(1, $published);
        self::assertFileExists($this->publicPath . 'modules/blog/js/app.js');
        self::assertFileDoesNotExist($this->publicPath . 'modules/blog/tools/backdoor.php');
        self::assertDirectoryDoesNotExist($this->publicPath . 'modules/blog/tools');
        self::assertFileDoesNotExist($this->publicPath . 'modules/blog/notes.md');
        self::assertFileDoesNotExist($this->publicPath . 'modules/blog/archive.phar');
    }

    public function testAModuleWithoutAssetsPublishesNothing(): void
    {
        $manifest = $this->module('vasya/blog', 'blog', []);

        self::assertSame(0, $this->publisher()->publish($manifest));
        self::assertDirectoryDoesNotExist($this->publicPath . 'modules/blog');
    }

    /**
     * Publishing again is how an update replaces what the previous version left, so what is no
     * longer shipped has to disappear rather than linger with a stale name.
     */
    public function testPublishingAgainReplacesWhatWasThere(): void
    {
        $publisher = $this->publisher();
        $publisher->publish($this->module('vasya/blog', 'blog', ['public/js/old.js' => 'old']));

        // The same module, one version later: the file it used to ship is not there any more.
        $publisher->publish($this->module('vasya/blog', 'blog', ['public/js/new.js' => 'new']));

        self::assertFileExists($this->publicPath . 'modules/blog/js/new.js');
        self::assertFileDoesNotExist($this->publicPath . 'modules/blog/js/old.js');
    }

    public function testUnpublishingTakesEverythingOut(): void
    {
        $publisher = $this->publisher();
        $publisher->publish($this->module('vasya/blog', 'blog', ['public/js/app.js' => 'x']));

        self::assertTrue($publisher->isPublished('blog'));

        $publisher->unpublish('blog');

        self::assertFalse($publisher->isPublished('blog'));
        self::assertDirectoryDoesNotExist($this->publicPath . 'modules/blog');
    }

    /**
     * A module keeping its assets somewhere else says so, and the directory it names is the one
     * that gets copied.
     */
    public function testAModuleMayKeepItsAssetsElsewhere(): void
    {
        $manifest = $this->module('vasya/blog', 'blog', ['dist/app.js' => 'x'], source: 'dist');

        self::assertSame(1, $this->publisher()->publish($manifest));
        self::assertFileExists($this->publicPath . 'modules/blog/app.js');
    }

    private function publisher(): ModuleAssetPublisher
    {
        return new ModuleAssetPublisher($this->publicPath);
    }

    /**
     * @param array<string, string> $files Path inside the module to its contents.
     */
    private function module(string $key, string $alias, array $files, string $source = 'public'): ModuleManifest
    {
        $path = $this->root . $key;

        // Each call describes the module as it is now, so what a previous call created is gone —
        // which is what a module replaced by a newer version looks like on disk.
        exec('rm -rf ' . escapeshellarg($path));

        foreach ($files as $file => $contents) {
            $full = $path . DS . $file;
            @mkdir(dirname($full), 0o777, true);
            file_put_contents($full, $contents);
        }

        @mkdir($path . DS . $source, 0o777, true);

        return new ModuleManifest(
            key: $key,
            alias: $alias,
            path: $path,
            name: 'Blog',
            version: '1.0.0',
            assets: new ModuleAssets(source: $source),
        );
    }
}
