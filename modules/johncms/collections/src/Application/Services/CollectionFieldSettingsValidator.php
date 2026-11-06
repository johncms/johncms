<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use JsonException;

final readonly class CollectionFieldSettingsValidator
{
    public function __construct(private CollectionFieldValueRules $values = new CollectionFieldValueRules())
    {
    }

    /** @param array<string, mixed> $settings */
    public function validate(FieldType $type, bool $multiple, array $settings, bool $required = false): void
    {
        try {
            if (strlen(json_encode($settings, JSON_THROW_ON_ERROR, 32)) > 65535 || ! $this->jsonData($settings) || ($settings !== [] && array_is_list($settings))) {
                throw new JsonException();
            }
        } catch (JsonException) {
            throw new CollectionSchemaException(d__('collections', 'Field settings must be a JSON object of at most 65535 bytes.'));
        }
        if (isset($settings['hint']) && (! is_string($settings['hint']) || mb_strlen($settings['hint']) > 2000)) {
            $this->fail(d__('collections', 'Use a hint of at most 2000 characters.'), ['hint']);
        }
        foreach (['min_length', 'max_length', 'max_values'] as $key) {
            if (! isset($settings[$key])) {
                continue;
            }
            $max = $key === 'max_values' ? 1000 : (in_array($type, [FieldType::String_, FieldType::Select, FieldType::File], true) ? 255 : 1000000);
            if (! is_int($settings[$key]) || $settings[$key] < ($key === 'max_values' ? 1 : 0) || $settings[$key] > $max) {
                $this->fail(d__('collections', 'Use valid integer limits for field length and number of values.'), [$key]);
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
                $this->fail(d__('collections', 'Numeric limits must be finite numbers on a numeric field.'), [$key]);
            }
        }
        foreach ([['min_length', 'max_length'], ['min_value', 'max_value']] as [$minimum, $maximum]) {
            if (isset($settings[$minimum], $settings[$maximum]) && $settings[$minimum] > $settings[$maximum]) {
                $this->fail(d__('collections', 'The minimum limit must not exceed the maximum.'), [$minimum, $maximum]);
            }
        }
        if ($type === FieldType::File && ! in_array($settings['file_storage'] ?? 'legacy', ['registry', 'legacy'], true)) {
            $this->fail(d__('collections', 'Choose registry or legacy storage for a file field.'), ['file_storage']);
        }
        if ($type === FieldType::File && ($settings['file_storage'] ?? 'legacy') === 'registry') {
            $bytes = $settings['max_file_bytes'] ?? CollectionFilePolicy::DEFAULT_MAX_BYTES;
            $mimes = $settings['allowed_mime_types'] ?? array_keys(CollectionFilePolicy::TYPES);
            if (! is_int($bytes) || $bytes < 1 || $bytes > 1073741824 || ! is_array($mimes) || ! array_is_list($mimes) || $mimes === [] || count($mimes) > count(CollectionFilePolicy::TYPES)) {
                $this->fail(d__('collections', 'Choose a file size limit from 1 byte to 1 GiB and at least one supported content type.'), ['max_file_bytes', 'allowed_mime_types']);
            }
            foreach ($mimes as $mime) {
                if (! is_string($mime) || ! isset(CollectionFilePolicy::TYPES[$mime])) {
                    $this->fail(d__('collections', 'Choose supported file content types.'), ['allowed_mime_types']);
                }
            }
        }
        if ($type === FieldType::Relation && (! is_int($settings['target_collection_id'] ?? null) || $settings['target_collection_id'] < 1)) {
            $this->fail(d__('collections', 'Choose a target collection for this relation field.'), ['target_collection_id']);
        }
        if ($type === FieldType::Relation && (! in_array($settings['on_delete'] ?? 'restrict', ['restrict', 'clear'], true) || ($required && ($settings['on_delete'] ?? 'restrict') === 'clear'))) {
            $this->fail(d__('collections', 'Use restrict or clear for relation deletion. Required relations must use restrict.'), ['relation_on_delete']);
        }
        if ($type === FieldType::Select) {
            $this->options($settings['options'] ?? []);
        }
        if (! isset($settings['default'])) {
            return;
        }
        $default = $settings['default'];
        if (($multiple && (! is_array($default) || ! array_is_list($default))) || (! $multiple && ! is_string($default))) {
            $this->fail(d__('collections', 'Use a string default for one value or a list of strings for multiple values.'), ['default']);
        }
        $defaults = is_array($default) ? $default : [$default];
        if (count($defaults) > ($settings['max_values'] ?? 1000)) {
            $this->fail(d__('collections', 'The default exceeds the configured number of values.'), ['default', 'max_values']);
        }
        foreach ($defaults as $index => $value) {
            if (! is_string($value) || ($value !== '' && $this->values->error($type, trim($value), $settings) !== null)) {
                $this->fail(d__('collections', 'The default does not meet this field type and its limits.'), [$multiple ? 'default_values.' . $index : 'default_value']);
            }
        }
    }

    private function options(mixed $options): void
    {
        if (! is_array($options) || ! array_is_list($options) || count($options) > 500) {
            $this->fail(d__('collections', 'Use a list of at most 500 options with code, label and availability.'), ['options']);
        }
        $codes = [];
        foreach ($options as $index => $option) {
            if (
                ! is_array($option) || ! is_string($option['code'] ?? null) || ! preg_match('/^[A-Za-z0-9_.+-]{1,255}$/D', $option['code'])
                || ! is_string($option['label'] ?? null) || trim($option['label']) === '' || mb_strlen($option['label']) > 255
                || (array_key_exists('active', $option) && ! is_bool($option['active'])) || array_diff(array_keys($option), ['code', 'label', 'active']) !== []
            ) {
                $this->fail(d__('collections', 'Each option needs a stable code, a label of at most 255 characters and boolean availability.'), ['option_code.' . $index, 'option_label.' . $index]);
            }
            $key = strtolower($option['code']);
            if (isset($codes[$key])) {
                $this->fail(d__('collections', 'Option codes must be unique regardless of letter case.'), ['option_code.' . $index]);
            }
            $codes[$key] = true;
        }
    }

    /** @param list<string> $fields */
    private function fail(string $message, array $fields): never
    {
        throw new CollectionSchemaException(message: $message, errors: array_fill_keys($fields, $message));
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
