<?php

class Controller
{
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
        if (($changelog = file_get_contents('../CHANGELOG.md')) !== false) {
            self::render('text', [
                'text' => (new Parsedown())->text($changelog),
            ]);
        } else {
            self::render('error', [
                'error_title' => 'Not found file',
                'error_message' => sprintf('Not found file by path "%s"', __DIR__ . '/../CHANGELOG.md'),
            ]);
        }
    }

    public function licenseRender(): void
    {
        if (($changelog = file_get_contents(__DIR__ . '/../LICENSE')) !== false) {
            self::render('text', [
                'text' => (new Parsedown())->text($changelog),
            ]);
        } else {
            self::render('error', [
                'error_title' => 'Not found file',
                'error_message' => sprintf('Not found file by path "%s"', __DIR__ . '/../LICENSE'),
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
