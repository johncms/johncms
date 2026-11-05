<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;

final readonly class CollectionFieldSettingsValidator
{
    public function __construct(private CollectionFieldValueRules $values = new CollectionFieldValueRules())
    {
    }

    /** @param array<string, mixed> $settings */
    public function validate(FieldType $type, bool $multiple, array $settings): void
    {
        try {
            if (strlen(json_encode($settings, JSON_THROW_ON_ERROR, 32)) > 65535 || ! $this->jsonData($settings) || ($settings !== [] && array_is_list($settings))) {
                throw new \JsonException();
            }
        } catch (\JsonException) {
            throw new CollectionSchemaException(d__('collections', 'Field settings must be a JSON object of at most 65535 bytes.'));
        }
        if (isset($settings['hint']) && (! is_string($settings['hint']) || mb_strlen($settings['hint']) > 2000)) {
            throw new CollectionSchemaException(d__('collections', 'Use a hint of at most 2000 characters.'));
        }
        foreach (['min_length', 'max_length', 'max_values'] as $key) {
            if (! isset($settings[$key])) {
                continue;
            }
            $max = $key === 'max_values' ? 1000 : (in_array($type, [FieldType::String_, FieldType::Select, FieldType::File], true) ? 255 : 1000000);
            if (! is_int($settings[$key]) || $settings[$key] < ($key === 'max_values' ? 1 : 0) || $settings[$key] > $max) {
                throw new CollectionSchemaException(d__('collections', 'Use valid integer limits for field length and number of values.'));
            }
        }
        foreach (['min_value', 'max_value'] as $key) {
            if (! isset($settings[$key])) {
                continue;
            }
            $number = $settings[$key];
            if (
                (! is_int($number) && ! is_float($number)) || ! is_finite($number)
                || ! in_array($type, [FieldType::Integer, FieldType::Double], true) || ($type === FieldType::Integer && ! is_int($number))
            ) {
                throw new CollectionSchemaException(d__('collections', 'Numeric limits must be finite numbers on a numeric field.'));
            }
        }
        if ((isset($settings['min_length'], $settings['max_length']) && $settings['min_length'] > $settings['max_length']) || (isset($settings['min_value'], $settings['max_value']) && $settings['min_value'] > $settings['max_value'])) {
            throw new CollectionSchemaException(d__('collections', 'The minimum limit must not exceed the maximum.'));
        }
        if ($type === FieldType::Select) {
            $this->options($settings['options'] ?? []);
        }
        if (! isset($settings['default'])) {
            return;
        }
        $default = $settings['default'];
        if (($multiple && (! is_array($default) || ! array_is_list($default))) || (! $multiple && ! is_string($default))) {
            throw new CollectionSchemaException(d__('collections', 'Use a string default for one value or a list of strings for multiple values.'));
        }
        $defaults = is_array($default) ? $default : [$default];
        if (count($defaults) > ($settings['max_values'] ?? 1000)) {
            throw new CollectionSchemaException(d__('collections', 'The default exceeds the configured number of values.'));
        }
        foreach ($defaults as $value) {
            if (! is_string($value) || ($value !== '' && $this->values->error($type, trim($value), $settings) !== null)) {
                throw new CollectionSchemaException(d__('collections', 'The default does not meet this field type and its limits.'));
            }
        }
    }

    private function options(mixed $options): void
    {
        if (! is_array($options) || ! array_is_list($options) || count($options) > 500) {
            throw new CollectionSchemaException(d__('collections', 'Use a list of at most 500 options with code, label and availability.'));
        }
        $codes = [];
        foreach ($options as $option) {
            if (
                ! is_array($option) || ! is_string($option['code'] ?? null) || ! preg_match('/^[A-Za-z0-9_.+-]{1,255}$/D', $option['code'])
                || ! is_string($option['label'] ?? null) || trim($option['label']) === '' || mb_strlen($option['label']) > 255
                || (array_key_exists('active', $option) && ! is_bool($option['active'])) || array_diff(array_keys($option), ['code', 'label', 'active']) !== []
            ) {
                throw new CollectionSchemaException(d__('collections', 'Each option needs a stable code, a label of at most 255 characters and boolean availability.'));
            }
            $key = strtolower($option['code']);
            if (isset($codes[$key])) {
                throw new CollectionSchemaException(d__('collections', 'Option codes must be unique regardless of letter case.'));
            }
            $codes[$key] = true;
        }
    }

    private function jsonData(mixed $value): bool
    {
        if (! is_array($value)) {
            return $value === null || is_scalar($value);
        }
        foreach ($value as $child) {
            if (! $this->jsonData($child)) {
                return false;
            }
        }
        return true;
    }
}
