<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

/**
 * The languages a text of the panel may be written in, as components/ui/language-switch.twig and
 * language-fallback-hint.twig draw them: the installed ones, which of them is the default of the
 * site, and which is opened first.
 *
 * The site shows such a text in the language of the visitor, then in the default language, then in
 * English, so the default language is the one worth opening first.
 */
final readonly class AdminLanguageOptions
{
    public const string LANGUAGES_URL = '/admin/languages';

    /**
     * @return list<array{code: string, name: string, default: bool}>
     */
    public function languages(): array
    {
        $default = $this->defaultCode();
        $languages = [];
        foreach (config('johncms')['lng_list'] ?? [] as $code => $data) {
            $languages[] = ['code' => (string) $code, 'name' => (string) ($data['name'] ?? $code), 'default' => $code === $default];
        }

        return $languages;
    }

    /**
     * @return array{code: string, name: string}
     */
    public function defaultLanguage(): array
    {
        $code = $this->defaultCode();
        foreach ($this->languages() as $language) {
            if ($language['code'] === $code) {
                return ['code' => $code, 'name' => $language['name']];
            }
        }

        return ['code' => $code, 'name' => $code];
    }

    /**
     * The language opened first: the one asked for when it is installed (after a failed save, the
     * one the administrator was writing in), else the default of the site, else the first.
     */
    public function active(string $requested = ''): string
    {
        $codes = array_column($this->languages(), 'code');
        foreach ([$requested, $this->defaultCode()] as $code) {
            if ($code !== '' && in_array($code, $codes, true)) {
                return $code;
            }
        }

        return $codes[0] ?? '';
    }

    private function defaultCode(): string
    {
        return (string) (config('johncms')['lng'] ?? 'en');
    }
}
