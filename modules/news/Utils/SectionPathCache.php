<?php

declare(strict_types=1);

namespace News\Utils;

use News\Models\NewsSection;
use Psr\Container\ContainerInterface;

class SectionPathCache
{
    protected $cache = [];
    private $cache_updated = false;
    private $cache_file = DATA_PATH . 'cache/news_sections_cache.php';

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
     * Gets the section path
     *
     * @param int $id
     * @return string
     */
    public function getSectionPath(int $id): string
    {
        $this->loadCache();
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        $section = (new NewsSection())->find($id);
        $path = [
            $section->code,
        ];
        $parent = $section->parentSection;
        while ($parent !== null) {
            $path[] = $parent->code;
            $parent = $parent->parentSection;
        }
        krsort($path);

        $section_url = implode('/', $path);
        $this->cache[$id] = $section_url;
        $this->cache_updated = true;
        return $section_url;
    }

    /**
     * Updates the cache file
     */
    public function __destruct()
    {
        if ($this->cache_updated) {
            $cache_data = "<?php\n\n" . 'return ' . var_export($this->cache, true) . ";\n";
            file_put_contents($this->cache_file, $cache_data);
        }
    }
}
