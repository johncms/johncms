<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Gettext\Generator\PoGenerator;
use Gettext\Scanner\PhpScanner;
use Gettext\Translations;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'i18n:scan',
    description: 'Scan source files and generate POT templates',
    aliases: ['scan'],
)]
final class I18nScanCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $xml = $this->loadConfig();
        if ($xml === null) {
            $output->writeln('<error>Configuration file translate.xml or translate.xml.dist not found, or contains errors.</error>');
            return self::FAILURE;
        }

        $domains = [];
        $translations = [];

        foreach ($xml->domain as $domain) {
            $domainName = trim((string) $domain->name);
            if ($domainName === '') {
                continue;
            }

            $domains[] = $domain;
            $translations[] = Translations::create($domainName);
        }

        if ($translations === []) {
            $output->writeln('<error>No valid domains found in translation config.</error>');
            return self::FAILURE;
        }

        $scanner = new PhpScanner(...$translations);
        $scanner->setFunctions(
            [
                '__'  => 'gettext',
                'd__' => 'dgettext',
                'n__' => 'ngettext',
                'dn__' => 'dngettext',
            ]
        );

        foreach ($domains as $domain) {
            $domainName = trim((string) $domain->name);
            $files = [];

            foreach ($domain->sourceDir as $directory) {
                $files = [...$files, ...$this->recursiveScan((string) $directory)];
            }

            foreach ($domain->sourceFile as $fileToScan) {
                $files[] = str_replace('\\', '/', (string) $fileToScan);
            }

            sort($files, SORT_STRING);
            $scanner->setDefaultDomain($domainName);

            foreach ($files as $file) {
                $scanner->scanFile($file);
            }
        }

        $generator = new PoGenerator();
        $scannedTranslations = $scanner->getTranslations();

        foreach ($domains as $domain) {
            $domainName = trim((string) $domain->name);
            $targetPath = rtrim((string) $domain->target, '/\\');
            $targetFile = $targetPath . '/' . $domainName . '.pot';
            $domainTranslations = $scannedTranslations[$domainName] ?? Translations::create($domainName);

            $generator->generateFile($domainTranslations, $targetFile);
        }

        $output->writeln('<info>Language templates have been created successfully.</info>');
        return self::SUCCESS;
    }

    private function loadConfig(): ?SimpleXMLElement
    {
        $configPath = null;
        if (is_file(ROOT_PATH . 'translate.xml')) {
            $configPath = ROOT_PATH . 'translate.xml';
        } elseif (is_file(ROOT_PATH . 'translate.xml.dist')) {
            $configPath = ROOT_PATH . 'translate.xml.dist';
        }

        if ($configPath === null) {
            return null;
        }

        $configContents = file_get_contents($configPath);
        if ($configContents === false) {
            return null;
        }

        $xml = simplexml_load_string($configContents);

        return $xml instanceof SimpleXMLElement ? $xml : null;
    }

    /**
     * @return list<string>
     */
    private function recursiveScan(string $folder): array
    {
        if (! is_dir($folder)) {
            return [];
        }

        $directory = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($directory);
        $fileList = [];

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (preg_match('/^.+\.(?:phtml|php)$/i', $path) === 1) {
                $fileList[] = $path;
            }
        }

        return $fileList;
    }
}
