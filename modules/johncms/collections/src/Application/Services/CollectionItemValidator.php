<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use Johncms\Validator\ValidatorInterface;

final readonly class CollectionItemValidator
{
    public function __construct(private ValidatorInterface $validator, private CollectionFieldValueRules $fieldValues = new CollectionFieldValueRules())
    {
    }

    /** @param Collection<int, ContentCollectionField> $definitions
     * @param array<string, mixed> $existingValues
     * @param list<string>|null $scope Error keys a partial write answers for; null validates everything.
     */
    public function validate(CollectionItemFormDTO $form, Collection $definitions, bool $applyDefaults = false, array $existingValues = [], bool $allowInactiveOptions = false, ?array $scope = null): CollectionItemFormDTO
    {
        $name = trim($form->name);
        $code = trim($form->code);
        $result = $this->validator->validate(['name' => $name, 'code' => $code], [
            'name' => [new StringLength(max: 255)],
            'code' => [new StringLength(max: 255, allowEmpty: true), new Pattern(
                pattern: '/^[\w.+-]+$/D',
                allowEmpty: true,
                message: d__('collections', 'Use letters, digits, dot, plus, hyphen or underscore in the code.')
            )],
        ]);
        $sort = filter_var($form->sort, FILTER_VALIDATE_INT, ['options' => ['min_range' => -2147483648, 'max_range' => 2147483647]]);
        if ($sort === false) {
            $result = $result->withError('sort', d__('collections', 'Enter an integer between -2147483648 and 2147483647.'));
        }
        if ($form->previewText !== null && strlen($form->previewText) > 65535) {
            $result = $result->withError('preview_text', d__('collections', 'The preview text must not exceed 65535 bytes.'));
        }
        $from = $this->fieldValues->date($form->activeFrom);
        $to = $this->fieldValues->date($form->activeTo);
        foreach (['active_from' => [$form->activeFrom, $from], 'active_to' => [$form->activeTo, $to]] as $key => [$raw, $date]) {
            if ($raw !== null && trim($raw) !== '' && $date === null) {
                $result = $result->withError($key, d__('collections', 'Enter a valid date and time.'));
            }
        }
        if ($from !== null && $to !== null && $from > $to) {
            $result = $result->withError('active_to', d__('collections', 'The end date must not precede the start date.'));
        }
        $values = [];
        foreach ($form->values as $key => $raw) {
            if (! $definitions->contains('code', $key)) {
                $result = $result->withError('values.' . $key, d__('collections', 'Unknown collection field.'));
            }
        }
        foreach ($definitions as $field) {
            $key = 'values.' . $field->code;
            if ($scope !== null && ! in_array($key, $scope, true)) {
                $values[$field->code] = $form->values[$field->code] ?? ($field->multiple ? [] : '');
                continue;
            }
            $raw = array_key_exists($field->code, $form->values) ? $form->values[$field->code] : ($applyDefaults ? ($field->settings['default'] ?? ($field->multiple ? [] : '')) : ($field->multiple ? [] : ''));
            if (($field->multiple && (! is_array($raw) || ! array_is_list($raw))) || (! $field->multiple && ! is_string($raw))) {
                $result = $result->withError($key, d__('collections', 'Submit a single value or a list as defined by the field.'));
                continue;
            }
            $list = is_array($raw) ? $raw : [$raw];
            $prepared = [];
            $old = $existingValues[$field->code] ?? [];
            $retained = array_values(array_filter(is_array($old) ? $old : [$old], is_string(...)));
            foreach ($list as $value) {
                if (! is_string($value)) {
                    $result = $result->withError($key, d__('collections', 'Submit a single value or a list as defined by the field.'));
                    continue;
                }
                $value = $field->role === 'tags' ? CollectionTextNormalizer::normalize($value) : trim($value);
                if ($value === '' && ($field->type !== FieldType::Boolean || $field->multiple)) {
                    continue;
                }
                $error = $this->fieldValues->error($field->type, $value, $field->settings ?? [], $retained, $allowInactiveOptions);
                if ($error !== null) {
                    $result = $result->withError($key, $error);
                    continue;
                }
                if (in_array($field->type, [FieldType::String_, FieldType::Select, FieldType::File], true)) {
                    $result = $result->merge($this->validator->validate([$key => $value], [$key => [new StringLength(max: 255)]]));
                }
                $prepared[] = match ($field->type) {
                    FieldType::Date => $this->fieldValues->date($value, true),
                    FieldType::Datetime => $this->fieldValues->date($value),
                    FieldType::Boolean => $value === '1' ? '1' : '0',
                    default => $value,
                };
            }
            if ($field->role === 'tags' || $field->type === FieldType::Select) {
                $prepared = array_values(array_unique($prepared));
            }
            if (count($prepared) > ($field->settings['max_values'] ?? 1000)) {
                $result = $result->withError($key, d__('collections', 'The field has too many values.'));
            }
            if ($field->required && ($field->type !== FieldType::Boolean || $field->multiple) && $prepared === [] && ! $result->hasError($key)) {
                $result = $result->withError($key, d__('collections', 'Fill in the field "%s"', $field->name));
            }
            $values[$field->code] = $field->multiple ? $prepared : ($prepared[0] ?? '');
        }
        if ($scope !== null) {
            $result = new ValidationResult(array_intersect_key($result->getErrors(), array_flip($scope)));
        }
        $result->throwIfInvalid(static fn (array $errors): CollectionItemFormException => new CollectionItemFormException($errors));
        return new CollectionItemFormDTO(
            $form->collectionId,
            $form->sectionId,
            $code,
            $name,
            $form->active,
            $from,
            $to,
            $sort,
            $form->previewText,
            $form->detailText,
            $values,
            $form->uploadContext
        );
    }
}
