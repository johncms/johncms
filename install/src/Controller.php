<?php

declare(strict_types=1);

namespace Install;

use Parsedown;

/**
 * Class Controller
 *
 * @package Install
 * @version 1.0
 * @author AkioSarkiz
 */
class Controller
{
    protected $path_license   = __DIR__ . '/../../LICENSE';
    protected $path_changelog = __DIR__ . '/../../CHANGELOG.md';

    /**
     * Render with default values
     *
     * @param $template
     * @param $arr
     */
    protected function render($template, $arr = []): void
    {
        die(Render::html($template, array_merge([
            'lang_arr' => Installer::$lang,
            'lang' => Installer::getLang(),
        ], $arr)));
    }

    public function changelogRender(): void
    {
        if (($changelog = file_get_contents($this->path_changelog)) !== false) {
            self::render('text', [
                'text' => (new Parsedown())->text($changelog),
            ]);
        } else {
            self::render('error', [
                'error_title' => 'Not found file',
                'error_message' => sprintf('Not found file by path "%s"', $this->path_changelog),
            ]);
        }
    }

    public function licenseRender(): void
    {
        if (($changelog = file_get_contents($this->path_license)) !== false) {
            self::render('text', [
                'text' => (new Parsedown())->text($changelog),
            ]);
        } else {
            self::render('error', [
                'error_title' => 'Not found file',
                'error_message' => sprintf('Not found file by path "%s"', $this->path_license),
            ]);
        }
    }

    public function finalRender(): void
    {
        $this->render('final');
    }

    public function setRender(): void
    {
        Installer::install();
        self::render('step2');
    }

    public function defaultRender(): void
    {
        self::render('step1');
    }
}
