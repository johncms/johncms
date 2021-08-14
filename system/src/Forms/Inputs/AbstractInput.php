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

    public function setHelpText(string $helpText): AbstractInput
    {
        $this->helpText = $helpText;
        return $this;
    }

    public function setName(string $name): AbstractInput
    {
        $this->name = $name;
        return $this;
    }

    public function setId(string $id): AbstractInput
    {
        $this->id = $id;
        return $this;
    }

    public function setLabel(string $label): AbstractInput
    {
        $this->label = $label;
        return $this;
    }

    public function setPlaceholder(string $placeholder): AbstractInput
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function setValue(mixed $value): AbstractInput
    {
        $this->value = $value;
        return $this;
    }

    public function setReadOnly(bool $readOnly): AbstractInput
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function setValidationRules(array $validationRules): AbstractInput
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    public function setCustomAttributes(array $customAttributes): AbstractInput
    {
        $this->customAttributes = $customAttributes;
        return $this;
    }

    public function setNameAndId(string $value): AbstractInput
    {
        $this->name = $value;
        $this->id = $value;
        return $this;
    }

    public function setLabelAndPlaceholder(string $value): AbstractInput
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
