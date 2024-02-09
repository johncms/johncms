<?php

declare(strict_types=1);

namespace Johncms;

class NavChain
{
    private array $items = [];
    private bool $showHomePage = true;
    private bool $lastIsActive = true;

    public function __invoke(): self
    {
        return $this;
    }

    public function addList(array $array): void
    {
        foreach ($array as $item) {
            $this->items[] = [
                'name' => $item[0],
                'url'  => isset($item[1]) ?: '',
            ];
        }
    }

    public function add(string $name, string $url = ''): void
    {
        $this->items[] = [
            'name' => $name,
            'url'  => $url,
        ];
    }

    public function getAll(): array
    {
        $allItems = $this->items;

        // Add homepage to breadcrumbs
        if ($this->showHomePage) {
            $homePageItem = [
                'name' => d__('system', 'Home'),
                'url'  => '/',
            ];
            $allItems = array_merge([$homePageItem], $allItems);
        }

        // Mark the last element as active
        if ($this->lastIsActive && ! empty($allItems)) {
            $lastKey = array_key_last($allItems);
            $allItems[$lastKey]['active'] = true;
        }

        return $allItems;
    }

    public function showHomePage(bool $value): void
    {
        $this->showHomePage = $value;
    }

    public function lastIsActive(bool $value): void
    {
        $this->lastIsActive = $value;
    }
}
