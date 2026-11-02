<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionDeclarationDTO
{
    /** @param list<CollectionFieldDeclarationDTO> $fields
     * @param array<string, mixed> $settings Defaults; existing administrator choices are retained.
     */
    public function __construct(
        public string $key,
        public string $code,
        public CollectionLabelDTO $label,
        public string $contentPermission,
        public array $fields = [],
        public array $settings = ['has_sections' => true, 'per_page' => 20],
    ) {
        if (
            ! preg_match('/^[a-z0-9_.-]{1,255}$/D', $key) || ! preg_match('/^(?!\.{1,2}$)[\w.+-]{1,255}$/D', $code)
            || ! preg_match('/^[a-z][a-z0-9_.-]+$/D', $contentPermission)
        ) {
            throw new \InvalidArgumentException('Invalid collection identity or content permission.');
        }
        $codes = $roles = $sources = [];
        foreach ($fields as $field) {
            if (! $field instanceof CollectionFieldDeclarationDTO || isset($codes[$field->code]) || ($field->role !== null && isset($roles[$field->role]))) {
                throw new \InvalidArgumentException('Collection declarations require unique field codes and roles.');
            }
            if ($field->renamedFrom !== null) {
                if ($field->renamedFrom === $field->code || isset($sources[$field->renamedFrom])) {
                    throw new \InvalidArgumentException('Each renamed field requires a distinct old code.');
                }
                $sources[$field->renamedFrom] = true;
            }
            $codes[$field->code] = true;
            if ($field->role !== null) {
                $roles[$field->role] = true;
            }
        }
        json_encode($settings, JSON_THROW_ON_ERROR);
    }
}
