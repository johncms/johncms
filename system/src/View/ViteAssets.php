<?php

declare(strict_types=1);

namespace Johncms\View;

use InvalidArgumentException;
use RuntimeException;

class ViteAssets
{
    private ?array $viteManifest = null;
    private bool $devMode = false;

    public function viteAssets(string $url): string
    {
        $result = '';
        $manifest = $this->getViteManifest();

        if ($this->devMode) {
            return '<script defer src="' . $manifest['url'] . $url . '" type="module"></script>';
        }

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
            $prodFile = PUBLIC_PATH . 'build/manifest.json';
            $devFile = PUBLIC_PATH . 'build/manifest.dev.json';
            if (is_file($devFile)) {
                $this->devMode = true;
                $manifestFileContent = file_get_contents($devFile);
            } elseif (is_file($prodFile)) {
                $manifestFileContent = file_get_contents($prodFile);
            } else {
                throw new RuntimeException('manifest.json file not found. Pleas run the "npm run build" command');
            }

            $this->viteManifest = json_decode($manifestFileContent, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->viteManifest;
    }
}
