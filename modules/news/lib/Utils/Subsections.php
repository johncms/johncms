<?php

declare(strict_types=1);

namespace News\Utils;

use News\Models\NewsSection;
use Psr\Container\ContainerInterface;

class Subsections
{
    protected $cache = [];
    private $cache_updated = false;
    private $cache_clear = false;
    private $cache_file = DATA_PATH . 'cache/news_subsections_cache.php';

    public function __invoke(ContainerInterface $container): self
    {
        return $this;
    }

    /**
     * Gets data from the cache
     */
    private function loadCache(): void
    {
        if (empty($this->cache) && file_exists($this->cache_file)) {
            $this->cache = require $this->cache_file;
        }
    }

    /**
     * Gets subsections
     *
     * @param NewsSection $section
     * @return array
     */
    public function getIds(NewsSection $section): array
    {
        $this->loadCache();
        if (array_key_exists($section->id, $this->cache)) {
            return $this->cache[$section->id];
        }

        $ids = $this->getSubsections($section->childSections);

        $this->cache[$section->id] = $ids;
        $this->cache_updated = true;
        return $ids;
    }

    /**
     * Set the flag for clearing the cache
     */
    public function clearCache(): void
    {
        $this->cache_clear = true;
    }

    /**
     * Recursively getting subsections.
     *
     * @param $subsections
     * @return array
     */
    private function getSubsections($subsections): array
    {
        $ids = [];
        foreach ($subsections as $subsection) {
            /** @var $subsection NewsSection */
            $ids[] = $subsection->id;
            $ids = array_merge($ids, $this->getSubsections($subsection->childSections));
        }

        return $ids;
    }

    /**
     * Updates the cache file
     */
    public function __destruct()
    {
        if ($this->cache_updated && ! $this->cache_clear) {
            $cache_data = "<?php\n\n" . 'return ' . var_export($this->cache, true) . ";\n";
            file_put_contents($this->cache_file, $cache_data);
        } elseif ($this->cache_clear) {
            @unlink($this->cache_file);
        }
    }
}
