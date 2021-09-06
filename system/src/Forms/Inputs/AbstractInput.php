<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forms\Inputs;

abstract class AbstractInput
{
    public string $type;

    public function __construct(
        public string $name = '',
        public string $id = '',
        public string $label = '',
        public string $placeholder = '',
        public mixed $value = null,
        public bool $readOnly = false,
        public string $helpText = '',
        public array $validationRules = [],
        public array $customAttributes = [],
    ) {
    }

    public function setHelpText(string $helpText): static
    {
        $this->helpText = $helpText;
        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function setPlaceholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function setReadOnly(bool $readOnly): static
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function setValidationRules(array $validationRules): static
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    public function setCustomAttributes(array $customAttributes): static
    {
        $this->customAttributes = $customAttributes;
        return $this;
    }

    public function setNameAndId(string $value): static
    {
        $this->name = $value;
        $this->id = $value;
        return $this;
    }

    public function setLabelAndPlaceholder(string $value): static
    {
        $this->label = $value;
        $this->placeholder = $value;
        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
