<?php

declare(strict_types=1);

namespace Johncms\Modules;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerModuleInstaller
{
    /**
     * Run the command to install module and get result
     *
     * @return array{output: string, success: bool}
     */
    public function install(string $moduleName): array
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $out = __('PHP binary path:') . ' <b>' . $phpBinaryPath . "</b>\n";

        $process = new Process(
            command: [$phpBinaryPath, ROOT_PATH . 'system/composer.phar', 'require', '--working-dir=' . ROOT_PATH, $moduleName],
            env:     ['COMPOSER_HOME' => CACHE_PATH . '.composer'],
        );
        $process->run();

        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $out .= "\nStdout: \n" . $data;
            } else { // $process::ERR === $type
                $out .= "\nStderr: \n" . $data;
            }
        }

        return [
            'output'  => $out,
            'success' => (new ComposerOutputParser($out))->moduleInstall($moduleName),
        ];
    }
}
