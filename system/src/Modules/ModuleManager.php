<?php

declare(strict_types=1);

namespace Johncms\Modules;

use Illuminate\Support\Str;
use Johncms\Database\Migration;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteDirectory;
use Psr\Log\LoggerInterface;

class ModuleManager
{
    private Filesystem $fileSystem;
    private string $assetsPath;
    private string $modulesPath;
    protected ?Installer $installer = null;
    protected ?LoggerInterface $logger = null;
    protected Migration $migration;

    public function __construct(
        private string $module
    ) {
        $this->fileSystem = FilesystemFactory::create();
        $this->assetsPath = Str::after(ASSETS_PATH, ROOT_PATH) . 'default' . DS;
        $this->modulesPath = Str::after(MODULES_PATH, ROOT_PATH);
        $this->logger = di(LoggerInterface::class);
        $this->migration = di(Migration::class);

        $className = $this->getInstallerClassName();
        if (class_exists($className) && is_subclass_of($className, Installer::class)) {
            $this->installer = new $className($this->module);
        }
    }

    protected function getInstallerClassName(): string
    {
        $namespace = Str::of($this->module)->explode('/')->map(function ($val) {
            return ucfirst($val);
        })->toArray();
        return '\\' . implode('\\', $namespace) . '\Install\Installer';
    }

    public function install(): void
    {
        $this->copyAssets();
        $this->migration->run($this->module);
        $this->installer?->install();
    }

    public function update(): void
    {
        $this->copyAssets();
        $this->migration->run($this->module);
    }

    public function uninstall(): void
    {
        $this->deleteAssets();
        $this->migration->rollback($this->module, ['step' => 10000]);
        $this->installer?->uninstall();
    }

    public function afterInstall(): void
    {
        $this->installer?->afterInstall();
    }

    public function afterUpdate(): void
    {
        $this->installer?->afterUpdate();
    }

    /**
     * Copy module assets to the default theme directory
     *
     * @return void
     */
    private function copyAssets(): void
    {
        $moduleAssetsPath = $this->assetsPath . $this->module;

        // Delete old directory if exists
        $this->deleteAssets();

        try {
            // Copy source assets to the default theme directory
            $moduleAssetsSource = $this->modulesPath . $this->module . DS . 'assets' . DS;
            if ($this->fileSystem->directoryExists($moduleAssetsSource)) {
                /** @var StorageAttributes[] $assets */
                $assets = $this->fileSystem->listContents($moduleAssetsSource, true);
                foreach ($assets as $asset) {
                    if ($asset->isFile()) {
                        $destinationPath = Str::after($asset->path(), $moduleAssetsSource);
                        $this->fileSystem->copy($asset->path(), $moduleAssetsPath . DS . $destinationPath);
                    }
                }
            }
        } catch (FilesystemException | UnableToDeleteDirectory $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Delete module assets
     *
     * @return void
     */
    private function deleteAssets(): void
    {
        $moduleAssetsPath = $this->assetsPath . $this->module;
        try {
            if ($this->fileSystem->directoryExists($moduleAssetsPath)) {
                $this->fileSystem->deleteDirectory($moduleAssetsPath);
            }
        } catch (FilesystemException | UnableToDeleteDirectory $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
