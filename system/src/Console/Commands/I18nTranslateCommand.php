<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Gettext\Generator\ArrayGenerator;
use Gettext\Loader\PoLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'i18n:translate',
    description: 'Convert PO files to lng.php dictionaries',
    aliases: ['translate'],
)]
final class I18nTranslateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loader = new PoLoader();
        $generator = new ArrayGenerator();
        $processedFiles = 0;

        foreach ($this->collectPoFiles() as $filePath) {
            $fileInfo = pathinfo($filePath);
            $basePath = $fileInfo['dirname'] . '/' . $fileInfo['filename'];

            $translations = $loader->loadFile($basePath . '.po');
            $generator->generateFile($translations, $basePath . '.lng.php');

            $processedFiles++;
        }

        $io->success('Languages have been successfully updated.');
        $io->text(sprintf('Processed .po files: %d', $processedFiles));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function collectPoFiles(): array
    {
        $patterns = [
            ROOT_PATH . 'system/locale/*.po',
            ROOT_PATH . 'modules/*/*/locale/*.po',
            PUBLIC_PATH . 'install/locale/*.po',
        ];

        $files = [];
        foreach ($patterns as $pattern) {
            $files = [...$files, ...(glob($pattern) ?: [])];
        }

        $files = array_values(array_unique($files));
        sort($files, SORT_STRING);

        return $files;
    }
}
