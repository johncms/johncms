<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Gettext\TranslatorFunctions;
use Johncms\Modules\Collections\Application\DTO\CollectionLabelDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;

final readonly class CollectionFormLayout
{
    /** @return array<string, string> */
    public function blocks(): array
    {
        return [
            'code' => d__('collections', 'Code'), 'preview_text' => d__('collections', 'Preview text'),
            'detail_text' => d__('collections', 'Detail text'), 'publication' => d__('collections', 'Publication'),
            'place' => d__('collections', 'Place'),
        ];
    }

    /** @param array<string, mixed> $layout */
    public function validate(array $layout, bool $registered): void
    {
        if (
            array_diff(array_keys($layout), ['fields', 'hidden_blocks']) !== []
            || (array_key_exists('fields', $layout) && ! is_array($layout['fields']))
            || (array_key_exists('hidden_blocks', $layout) && ! is_array($layout['hidden_blocks']))
        ) {
            throw $this->invalid();
        }
        $hidden = $layout['hidden_blocks'] ?? [];
        if (! array_is_list($hidden) || count(array_unique($hidden, SORT_REGULAR)) !== count($hidden)) {
            throw $this->invalid();
        }
        foreach ($hidden as $block) {
            if (! is_string($block) || ! array_key_exists($block, $this->blocks())) {
                throw $this->invalid();
            }
        }
        foreach ($layout['fields'] ?? [] as $code => $settings) {
            if (! is_string($code) || ! preg_match('/^[a-z0-9_-]{1,255}$/D', $code) || ! is_array($settings) || array_diff(array_keys($settings), ['group', 'sort']) !== []) {
                throw $this->invalid();
            }
            if (array_key_exists('sort', $settings) && (! is_int($settings['sort']) || abs($settings['sort']) > 1000000)) {
                throw $this->invalid();
            }
            if (array_key_exists('group', $settings)) {
                $group = $settings['group'];
                if ($registered && is_array($group) && count($group) === 2 && isset($group['domain'], $group['message']) && is_string($group['domain']) && is_string($group['message'])) {
                    try {
                        new CollectionLabelDTO($group['domain'], $group['message']);
                    } catch (\InvalidArgumentException) {
                        throw $this->invalid();
                    }
                } elseif (! is_string($group) || mb_strlen($group) > 120 || preg_match('/[\x00-\x1f\x7f]/', $group)) {
                    throw $this->invalid();
                }
            }
        }
    }

    /** @return array<string, mixed> */
    public function settings(ContentCollection $collection): array
    {
        $default = $collection->settings['_workspace']['layout'] ?? [];
        $custom = $collection->settings['workspace']['layout'] ?? [];
        return [
            'fields' => array_replace_recursive($default['fields'] ?? [], $custom['fields'] ?? []),
            'hidden_blocks' => $custom['hidden_blocks'] ?? $default['hidden_blocks'] ?? [],
        ];
    }

    /** @return list<string> */
    public function hidden(ContentCollection $collection): array
    {
        return $this->settings($collection)['hidden_blocks'];
    }

    /** @param list<array<string, mixed>> $definitions
     * @return list<array{title: string, fields: list<array<string, mixed>>}>
     */
    public function groups(ContentCollection $collection, array $definitions): array
    {
        $settings = $this->settings($collection)['fields'];
        foreach ($definitions as &$definition) {
            $config = $settings[$definition['code']] ?? [];
            $group = $config['group'] ?? '';
            $definition['layout_group'] = is_array($group) ? TranslatorFunctions::getTranslator()->dgettext($group['domain'], $group['message']) : $group;
            $definition['layout_sort'] = $config['sort'] ?? $definition['sort'];
        }
        unset($definition);
        usort($definitions, static fn (array $a, array $b): int => $a['layout_sort'] <=> $b['layout_sort']);
        $groups = [];
        foreach ($definitions as $definition) {
            $key = $definition['layout_group'];
            $groups[$key] ??= ['title' => $key !== '' ? $key : d__('collections', 'Fields of the collection'), 'fields' => []];
            $groups[$key]['fields'][] = $definition;
        }
        return array_values($groups);
    }

    private function invalid(): CollectionSchemaException
    {
        return new CollectionSchemaException(d__('collections', 'Choose valid field groups, numeric ordering and available blocks. The name is always visible.'));
    }
}
