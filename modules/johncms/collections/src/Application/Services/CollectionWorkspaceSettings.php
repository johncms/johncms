<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Gettext\TranslatorFunctions;
use InvalidArgumentException;
use Johncms\Modules\Collections\Application\DTO\CollectionLabelDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use JsonException;

final readonly class CollectionWorkspaceSettings
{
    public const array LABELS = ['singular', 'plural', 'new', 'create', 'edit', 'delete'];

    /** @param array<string, mixed> $settings */
    public function validate(array $settings, bool $registered = false): void
    {
        if (($settings !== [] && array_is_list($settings)) || array_diff(array_keys($settings), ['labels', 'layout', 'listing']) !== [] || (array_key_exists('labels', $settings) && ! is_array($settings['labels']))) {
            throw $this->invalid();
        }
        if (array_key_exists('layout', $settings)) {
            if (! is_array($settings['layout'])) {
                throw $this->invalid();
            }
            (new CollectionFormLayout())->validate($settings['layout'], $registered);
        }
        if (array_key_exists('listing', $settings)) {
            if (! is_array($settings['listing'])) {
                throw $this->invalid();
            }
            (new CollectionListSettings())->validate($settings['listing']);
        }
        foreach ($settings['labels'] ?? [] as $key => $label) {
            if (! in_array($key, self::LABELS, true)) {
                throw $this->invalid();
            }
            if ($registered && is_array($label) && count($label) === 2 && array_diff(array_keys($label), ['domain', 'message']) === [] && is_string($label['domain']) && is_string($label['message'])) {
                try {
                    new CollectionLabelDTO($label['domain'], $label['message']);
                } catch (InvalidArgumentException) {
                    throw $this->invalid();
                }
            } elseif (! is_string($label) || trim($label) === '' || mb_strlen($label) > 120 || preg_match('/[\x00-\x1f\x7f]/', $label)) {
                throw $this->invalid();
            }
        }
        try {
            if (strlen(json_encode($settings, JSON_THROW_ON_ERROR, 16)) > 16384) {
                throw $this->invalid();
            }
        } catch (JsonException) {
            throw $this->invalid();
        }
    }

    /** @return array<string, string> */
    public function labels(ContentCollection $collection): array
    {
        $settings = $collection->settings ?? [];
        return array_replace($this->defaults($collection), $settings['workspace']['labels'] ?? []);
    }

    /** @return array<string, string> */
    public function defaults(?ContentCollection $collection = null): array
    {
        $labels = [
            'singular' => d__('collections', 'Item'), 'plural' => d__('collections', 'Items'),
            'new' => d__('collections', 'New item'), 'create' => d__('collections', 'New item'),
            'edit' => d__('collections', 'Edit item'), 'delete' => d__('collections', 'Delete item'),
        ];
        foreach ($collection?->settings['_workspace']['labels'] ?? [] as $key => $label) {
            $labels[$key] = is_array($label) ? TranslatorFunctions::getTranslator()->dgettext($label['domain'], $label['message']) : $label;
        }
        return $labels;
    }

    /** JSON object key order differs between database engines.
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     */
    public function same(array $first, array $second): bool
    {
        return $this->canonical($first) === $this->canonical($second);
    }

    private function canonical(mixed $value): mixed
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }
            return array_map($this->canonical(...), $value);
        }
        return $value;
    }

    /** @return array<string, string> */
    public function labelTitles(): array
    {
        return [
            'singular' => d__('collections', 'Record name (singular)'), 'plural' => d__('collections', 'Record list title'),
            'new' => d__('collections', 'New record page title'), 'create' => d__('collections', 'Create action label'),
            'edit' => d__('collections', 'Edit action label'), 'delete' => d__('collections', 'Delete action label'),
        ];
    }

    private function invalid(): CollectionSchemaException
    {
        return new CollectionSchemaException(d__('collections', 'Use known workspace labels with nonempty text of at most 120 characters.'));
    }
}
