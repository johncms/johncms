<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Johncms\Container\ContainerFactory;
use Johncms\View\MetaTagManager;
use PHPUnit\Framework\TestCase;

class MetaTagManagerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = ContainerFactory::getContainer();

        // Replace default values
        $config = $container->get('config');
        $config['johncms']['meta_title'] = 'title';
        $config['johncms']['meta_description'] = 'description';
        $config['johncms']['meta_keywords'] = 'keywords';

        $container->instance('config', $config);
    }

    public function testDefaults()
    {
        $metaTags = new MetaTagManager();

        // Test default values
        $this->assertEquals('title', $metaTags->getTitle());
        $this->assertEquals('description', $metaTags->getDescription());
        $this->assertEquals('keywords', $metaTags->getKeywords());
        $this->assertEquals('', $metaTags->getCanonical());
        $this->assertEquals('', $metaTags->getPageTitle());
    }

    public function testCustomValues()
    {
        $metaTags = new MetaTagManager();

        $metaTags->setCanonical('https://example.com');
        $this->assertEquals('https://example.com', $metaTags->getCanonical());

        $metaTags->setKeywords('kw1, kw2, kw3');
        $this->assertEquals('kw1, kw2, kw3', $metaTags->getKeywords());

        $metaTags->setDescription('My new description');
        $this->assertEquals('My new description', $metaTags->getDescription());

        $metaTags->setTitle('My meta title');
        $this->assertEquals('My meta title', $metaTags->getTitle());

        $metaTags->setPageTitle('My page title');
        $this->assertEquals('My page title', $metaTags->getPageTitle());

        $metaTags->setAll('All tags are the same');
        $this->assertEquals('All tags are the same', $metaTags->getTitle());
        $this->assertEquals('All tags are the same', $metaTags->getDescription());
        $this->assertEquals('All tags are the same', $metaTags->getKeywords());
        $this->assertEquals('All tags are the same', $metaTags->getPageTitle());
    }
}
