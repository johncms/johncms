<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Gettext\Generator\ArrayGenerator;
use Gettext\Loader\PoLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('translate:generate', 'Generate lng.php files from the .po translation files')]
class TranslateGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('Generate lng.php files from the .po translation files')
            ->addArgument('clear', InputArgument::OPTIONAL, 'Remove .po files.', 'n');
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $loader = new PoLoader();
        $generator = new ArrayGenerator();

        $search = [ROOT_PATH . 'modules/*/', ROOT_PATH . 'public/install/', ROOT_PATH . 'system/'];

        foreach ($search as $dir) {
            $files = glob($dir . 'locale/*.po');

            foreach ($files as $file) {
                $file = pathinfo($file);
                $file = $file['dirname'] . '/' . $file['filename'];
                $translations = $loader->loadFile($file . '.po');
                $generator->generateFile($translations, $file . '.lng.php');

                // Remove .po file if the clear argument is "y"
                if (strtolower($input->getArgument('clear')) === 'y') {
                    unlink($file . '.po');
                }
            }
        }

        $style->success('Languages has been successfully updated');
        return Command::SUCCESS;
    }
}
