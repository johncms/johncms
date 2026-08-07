<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Gettext\Generator\PoGenerator;
use Gettext\Scanner\PhpScanner;
use Gettext\Translations;
use Johncms\System\i18n\TwigScanner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand(
    name: 'i18n:scan',
    description: 'Scan source files and generate POT templates',
    aliases: ['scan'],
)]
final class I18nScanCommand extends Command
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Environment $mailTwig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $xml = $this->loadConfig();
        if ($xml === null) {
            $io->error('Configuration file translate.xml or translate.xml.dist not found, or contains errors.');
            return self::FAILURE;
        }

        $domains = [];
        $translations = [];
        $translationsByDomain = [];

        foreach ($xml->domain as $domain) {
            $domainName = trim((string) $domain->name);
            if ($domainName === '') {
                continue;
            }

            $domains[] = $domain;
            $domainTranslations = Translations::create($domainName);
            $translations[] = $domainTranslations;
            $translationsByDomain[$domainName] = $domainTranslations;
        }

        if ($translations === []) {
            $io->error('No valid domains found in translation config.');
            return self::FAILURE;
        }

        $twigScanner = new TwigScanner($this->twig, $translationsByDomain, $this->mailTwig);
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
            $twigScanner->setDefaultDomain($domainName);

            foreach ($files as $file) {
                if (str_ends_with($file, '.twig')) {
                    $twigScanner->scanFile($file);
                    continue;
                }

                $scanner->scanFile($file);
            }
        }

        $generator = new PoGenerator();
        // The Twig scanner writes into the very objects the PHP scanner was built with, so both
        // passes end up in one .pot per domain.
        $scannedTranslations = $scanner->getTranslations();

        foreach ($domains as $domain) {
            $domainName = trim((string) $domain->name);
            $targetPath = rtrim((string) $domain->target, '/\\');
            $targetFile = $targetPath . '/' . $domainName . '.pot';
            $domainTranslations = $scannedTranslations[$domainName] ?? Translations::create($domainName);

            $generator->generateFile($domainTranslations, $targetFile);
        }

        $io->success('Language templates have been created successfully.');
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
            if (preg_match('/^.+\.(?:twig|phtml|php)$/i', $path) === 1) {
                $fileList[] = $path;
            }
        }

        return $fileList;
    }
}
