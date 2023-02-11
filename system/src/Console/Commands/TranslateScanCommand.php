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

use Gettext\Generator\PoGenerator;
use Gettext\Scanner\PhpScanner;
use Gettext\Translations;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('translate:scan', 'Scan phrases and generate .pot files')]
class TranslateScanCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('Scan phrases and generate .pot files');
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        if (is_file($s = ROOT_PATH . 'translate.xml') === true || is_file($s = ROOT_PATH . 'translate.xml.dist') === true) {
            $xml = simplexml_load_string(file_get_contents($s));
        }

        if (! isset($xml) || false === $xml) {
            $style->error('ERROR: Configuration file not found, or contains errors.');
            return Command::FAILURE;
        }

        foreach ($xml->domain as $domain) {
            $list = [];
            foreach ($domain->sourceDir as $directory) {
                $list = array_merge($list, $this->recursiveScan((string) $directory, '/^.+\.(?:phtml|php)$/i'));
            }

            if (isset($domain->sourceFile)) {
                foreach ($domain->sourceFile as $fileToScan) {
                    $list[] = (string) $fileToScan;
                }
            }

            $domainName = (string) $domain->name;

            sort($list, SORT_STRING);
            $phpScanner = new PhpScanner(Translations::create($domainName));
            $phpScanner->setDefaultDomain($domainName);

            foreach ($list as $file) {
                $phpScanner->scanFile($file);
            }

            $name = (string) $domain->name;
            $generator = new PoGenerator();
            $generator->generateFile($phpScanner->getTranslations()[$name], $domain->target . '/' . $name . '.pot');
        }

        $style->success('Language templates has been created successfully');
        return Command::SUCCESS;
    }

    private function recursiveScan(string $folder, string $pattern): array
    {
        $dir = new RecursiveDirectoryIterator($folder);
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
        $fileList = [];

        foreach ($files as $file) {
            $fileList[] = str_replace('\\', '/', $file[0]);
        }

        return $fileList;
    }
}
