<?php

declare(strict_types=1);

namespace Johncms\View;

use InvalidArgumentException;

class ViteAssets
{
    private ?array $viteManifest = null;

    public function viteAssets(string $url): string
    {
        $result = '';
        $manifest = $this->getViteManifest();
        if (array_key_exists($url, $manifest)) {
            $manifestData = $manifest[$url];
            $result .= '<script defer src="/build/' . $manifestData['file'] . '" type="module"></script>';
            if (! empty($manifestData['css'])) {
                foreach ($manifestData['css'] as $css) {
                    $result .= '<link rel="stylesheet" href="/build/' . $css . '">';
                }
            }
            return $result;
        }

        throw new InvalidArgumentException('Unable to locate the asset: ' . $url);
    }

    private function getViteManifest()
    {
        if (! $this->viteManifest) {
            if (is_file(PUBLIC_PATH . 'build/manifest.json')) {
                $this->viteManifest = json_decode(file_get_contents(PUBLIC_PATH . 'build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw new \RuntimeException('manifest.json file not found. Pleas run the "npm run build" command');
            }
        }

        return $this->viteManifest;
    }
}
