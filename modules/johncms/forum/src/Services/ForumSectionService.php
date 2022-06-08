<?php

declare(strict_types=1);

namespace Johncms\Forum\Services;

use Johncms\Forum\Models\ForumSection;

class ForumSectionService
{
    public function getTree(): array
    {
        $sections = ForumSection::query()->get()->toArray();
        return $this->buildTree($sections);
    }

    private function buildTree(array &$elements, $parentId = 0): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[$element['id']] = $element;
                unset($elements[$element['id']]);
            }
        }
        return $branch;
    }
}
